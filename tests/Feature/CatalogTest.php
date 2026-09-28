<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_combines_specifications_and_category_without_exposing_drafts(): void
    {
        $category = Category::factory()->create(['name' => 'Mobile Legends', 'slug' => 'mobile-legends']);
        $account = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'Akun unggulan']);
        $draft = GameAccount::factory()->create(['category_id' => $category->id, 'status' => 'draft', 'title' => 'Rahasia draft']);
        GameAccount::factory()->create(['title' => 'Tidak cocok', 'specifications' => 'Rank: Gold']);
        foreach (['Mythic', '150', 'Fanny', '60', 'Sword', 'Asia', 'Mobile Mythic', 'pilihan', 'unggulan'] as $keyword) {
            $this->get('/?'.http_build_query(['q' => $keyword, 'category' => $category->slug]))->assertOk()->assertSee($account->title)->assertDontSee('Rahasia draft')->assertDontSee('Tidak cocok');
        }
        $this->get('/akun/'.$draft->slug)->assertNotFound();
        $this->get('/kategori/mobile-legends')->assertOk()->assertSee($account->title);
        $this->get('/?q=unknown-keyword')->assertOk()->assertSee('Belum ada akun yang cocok');
    }

    public function test_whatsapp_message_is_encoded_and_sold_accounts_have_no_contact_button(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $account = GameAccount::factory()->create(['title' => 'Mythic & Hero']);
        $this->assertSame('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya tertarik dengan akun Mythic & Hero seharga Rp 350.000. Apakah masih tersedia?'), $account->whatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Hubungi admin via WhatsApp');
        $account->update(['status' => 'sold']);
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('Hubungi admin via WhatsApp')->assertSee('sudah terjual');
        $this->get('/?available=1')->assertDontSee('Mythic &amp; Hero', false);
    }

    public function test_admin_authentication_protects_all_management_pages_and_mutations(): void
    {
        foreach (['/admin', '/admin/accounts', '/admin/categories', '/admin/settings', '/admin/accounts/create'] as $path) {
            $this->get($path)->assertRedirect('/admin/login');
        }
        $this->post('/admin/accounts', [])->assertRedirect('/admin/login');
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-password'])->assertRedirect('/admin/accounts/create');
        $this->assertAuthenticatedAs($user);
        foreach (['/admin', '/admin/accounts', '/admin/categories', '/admin/settings', '/admin/accounts/create'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->post('/admin/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_admin_can_create_update_and_delete_account_and_images(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $data = ['category_id' => $category->id, 'title' => 'Akun baru', 'price' => 200000, 'description' => 'Deskripsi akun', 'specifications' => 'Rank Mythic', 'status' => 'available'];
        $this->post('/admin/accounts', $data + ['main_image' => UploadedFile::fake()->createWithContent('main.png', $png), 'images' => [UploadedFile::fake()->createWithContent('extra.png', $png)]])->assertSessionHasNoErrors()->assertRedirect('/admin/accounts');
        $account = GameAccount::firstOrFail();
        Storage::disk('public')->assertExists($account->main_image);
        $image = $account->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->image_path);
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk();
        $this->put('/admin/accounts/'.$account->id, array_merge($data, ['status' => 'sold', 'remove_images' => [$image->id]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'status' => 'sold']);
        Storage::disk('public')->assertMissing($image->image_path);
        $this->delete('/admin/accounts/'.$account->id)->assertRedirect();
        Storage::disk('public')->assertMissing($account->main_image);
        $this->assertDatabaseMissing('game_accounts', ['id' => $account->id]);
    }

    public function test_category_crud_and_used_category_deletion_guard(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/categories', ['name' => 'Valorant'])->assertSessionHasNoErrors();
        $category = Category::firstOrFail();
        $this->put('/admin/categories/'.$category->id, ['name' => 'Valorant Asia'])->assertSessionHasNoErrors();
        $account = GameAccount::factory()->create(['category_id' => $category->id]);
        $this->delete('/admin/categories/'.$category->id)->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'slug' => 'valorant-asia']);
        $account->delete();
        $this->delete('/admin/categories/'.$category->id)->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_invalid_uploads_and_whatsapp_settings_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/accounts', ['main_image' => UploadedFile::fake()->create('script.php', 2, 'text/plain')])->assertSessionHasErrors(['main_image', 'title', 'category_id']);
        $this->put('/admin/settings', ['store_name' => 'Nexplay', 'whatsapp_number' => '+62 invalid', 'whatsapp_default_message' => 'Halo'])->assertSessionHasErrors('whatsapp_number');
        $this->put('/admin/settings', ['store_name' => 'Toko Game', 'whatsapp_number' => '6281234567890', 'whatsapp_default_message' => 'Halo {nama_akun}, {harga} {url}'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'store_name', 'value' => 'Toko Game']);
    }

    public function test_pagination_keeps_search_and_filter_parameters(): void
    {
        $category = Category::factory()->create();
        GameAccount::factory()->count(13)->create(['category_id' => $category->id]);
        $response = $this->get('/?'.http_build_query(['q' => 'Mythic', 'category' => $category->slug]));
        $response->assertOk()->assertSee('page=2', false)->assertSee('q=Mythic', false);
        $this->assertCount(12, $response->viewData('accounts'));
    }
}
