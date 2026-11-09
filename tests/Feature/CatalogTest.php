<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\ManualSale;
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

    public function test_trade_in_button_opens_whatsapp_only_for_available_accounts(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $account = GameAccount::factory()->create(['title' => 'Mythic & Hero']);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya ingin mengajukan tukar tambah untuk akun Mythic & Hero seharga Rp 350.000.'), $account->tradeInWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Ajukan tukar tambah');

        $account->update(['status' => 'sold']);

        $this->assertNull($account->tradeInWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('Ajukan tukar tambah');
    }

    public function test_account_with_seller_number_contacts_the_seller_instead_of_the_store(): void
    {
        $sellerAccount = GameAccount::factory()->create(['seller_whatsapp' => '6289999999999']);
        $storeAccount = GameAccount::factory()->create();

        $this->assertStringStartsWith('https://wa.me/6289999999999?text=', $sellerAccount->whatsappUrl());
        $this->assertNull($storeAccount->whatsappUrl());
        $this->get('/akun/'.$sellerAccount->slug)->assertOk()->assertSee('Hubungi penjual via WhatsApp')->assertSee('https://wa.me/6289999999999?text=', false);

        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);

        $this->assertStringStartsWith('https://wa.me/6289999999999?text=', $sellerAccount->whatsappUrl());
        $this->assertStringStartsWith('https://wa.me/6289999999999?text=', $sellerAccount->tradeInWhatsappUrl());
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $storeAccount->whatsappUrl());

        $sellerAccount->update(['status' => 'sold']);

        $this->assertNull($sellerAccount->whatsappUrl());
    }

    public function test_admin_can_set_clear_and_is_warned_about_an_invalid_seller_number(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'price' => $account->price, 'description' => 'Deskripsi', 'specifications' => 'Rank Mythic', 'status' => 'available'];

        $this->put('/admin/accounts/'.$account->id, $data + ['seller_whatsapp' => '6289999999999'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'seller_whatsapp' => '6289999999999']);
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('value="6289999999999"', false);

        $this->put('/admin/accounts/'.$account->id, $data + ['seller_whatsapp' => '0812 3456'])->assertSessionHasErrors(['seller_whatsapp' => 'Nomor penjual harus diawali kode negara tanpa tanda +, contoh 6281234567890.']);
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'seller_whatsapp' => '6289999999999']);

        $this->put('/admin/accounts/'.$account->id, $data + ['seller_whatsapp' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'seller_whatsapp' => null]);
    }

    public function test_sales_page_requires_login_and_reports_only_accounts_sold_in_the_chosen_month(): void
    {
        $this->get('/admin/sales')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        GameAccount::factory()->create(['title' => 'Terjual Oktober', 'status' => 'sold', 'price' => 200000])->forceFill(['sold_at' => '2026-10-31 23:00:00'])->save();
        GameAccount::factory()->create(['title' => 'Terjual Awal Oktober', 'status' => 'sold', 'price' => 100000])->forceFill(['sold_at' => '2026-10-01 00:00:00'])->save();
        GameAccount::factory()->create(['title' => 'Terjual November', 'status' => 'sold', 'price' => 900000])->forceFill(['sold_at' => '2026-11-01 00:00:00'])->save();
        GameAccount::factory()->create(['title' => 'Masih Tersedia']);

        $response = $this->get('/admin/sales?month=2026-10');

        $response->assertOk()->assertSee('Terjual Oktober')->assertSee('Terjual Awal Oktober')->assertDontSee('Terjual November')->assertDontSee('Masih Tersedia');
        $this->assertSame(2, $response->viewData('monthCount'));
        $this->assertSame(300000, $response->viewData('monthRevenue'));
        $this->assertSame(3, $response->viewData('totalCount'));
        $this->assertSame(1200000, $response->viewData('totalRevenue'));
        $this->get('/admin/sales?month=2026-09')->assertOk()->assertSee('Belum ada akun terjual');
        $this->get('/admin/sales?month=oktober')->assertSessionHasErrors('month');
    }

    public function test_manual_sales_require_login_and_can_be_added_counted_and_deleted(): void
    {
        $this->post('/admin/sales', [])->assertRedirect('/admin/login');
        $existing = ManualSale::factory()->create();
        $this->delete('/admin/sales/'.$existing->id)->assertRedirect('/admin/login');
        $this->assertDatabaseHas('manual_sales', ['id' => $existing->id]);
        $this->actingAs(User::factory()->create());
        GameAccount::factory()->create(['title' => 'Akun Oktober', 'status' => 'sold', 'price' => 200000])->forceFill(['sold_at' => '2026-10-10 10:00:00'])->save();

        $this->post('/admin/sales', ['item' => 'Joki Legend 5 bintang', 'amount' => 60000, 'sold_on' => '2026-10-31', 'note' => 'Budi'])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/sales?month=2026-10');

        $response = $this->get('/admin/sales?month=2026-10');

        $response->assertOk()->assertSee('Joki Legend 5 bintang')->assertSee('Budi')->assertSee('Akun Oktober');
        $this->assertSame(3, $response->viewData('monthCount'));
        $this->assertSame(320000, $response->viewData('monthRevenue'));
        $this->assertSame(3, $response->viewData('totalCount'));
        $this->get('/admin/sales?month=2026-11')->assertOk()->assertDontSee('Joki Legend 5 bintang');

        $this->delete('/admin/sales/'.$existing->id)->assertRedirect();
        $this->assertDatabaseMissing('manual_sales', ['id' => $existing->id]);
    }

    public function test_manual_sale_rejects_missing_or_invalid_values(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/sales', ['amount' => 0, 'sold_on' => 'kemarin'])->assertSessionHasErrors(['item', 'amount', 'sold_on']);
        $this->assertDatabaseCount('manual_sales', 0);
    }

    public function test_sold_date_follows_status_changes_and_is_kept_on_unrelated_edits(): void
    {
        $account = GameAccount::factory()->create();
        $this->assertNull($account->sold_at);

        $this->travelTo('2026-10-15 10:00:00');
        $account->update(['status' => 'sold']);
        $this->assertSame('2026-10-15', $account->sold_at->toDateString());

        $this->travelTo('2026-10-20 10:00:00');
        $account->update(['price' => 1000]);
        $this->assertSame('2026-10-15', $account->fresh()->sold_at->toDateString());

        $account->update(['status' => 'available']);
        $this->assertNull($account->fresh()->sold_at);
    }

    public function test_admin_authentication_protects_all_management_pages_and_mutations(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Username')->assertDontSee('type="email"', false);

        foreach (['/admin', '/admin/accounts', '/admin/categories', '/admin/settings', '/admin/accounts/create'] as $path) {
            $this->get($path)->assertRedirect('/admin/login');
        }
        $this->post('/admin/accounts', [])->assertRedirect('/admin/login');
        $user = User::factory()->create(['username' => 'admin-toko', 'password' => 'correct-password']);
        $this->post('/admin/login', ['username' => $user->username, 'password' => 'incorrect'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'correct-password'])->assertSessionHasErrors('username');
        $this->post('/admin/login', ['username' => $user->username, 'password' => 'correct-password'])->assertRedirect('/admin/accounts/create');
        $this->assertAuthenticatedAs($user);
        foreach (['/admin', '/admin/accounts', '/admin/categories', '/admin/settings', '/admin/accounts/create'] as $path) {
            $this->get($path)->assertOk()->assertSee('Admin workspace')->assertDontSee('auth()->');
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
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('value="sold"', false)->assertSee('Terjual');
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
        $this->delete('/admin/accounts/'.$account->id)->assertRedirect();
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

    public function test_admin_search_matches_every_status_and_reports_no_results(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create(['name' => 'Valorant']);
        GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'Akun Radiant', 'status' => 'draft']);
        GameAccount::factory()->create(['title' => 'Tidak cocok']);

        foreach (['Radiant', 'Valorant', 'Valorant Radiant'] as $keyword) {
            $this->get('/admin/accounts?'.http_build_query(['q' => $keyword]))->assertOk()->assertSee('Akun Radiant')->assertDontSee('Tidak cocok')->assertSee('1 akun cocok');
        }
        $this->get('/admin/accounts')->assertOk()->assertSee('Akun Radiant')->assertSee('Tidak cocok')->assertSee('2 akun dalam katalog');
        $this->get('/admin/accounts?q=unknown-keyword')->assertOk()->assertSee('Tidak ada akun yang cocok')->assertDontSee('Belum ada akun');
    }

    public function test_admin_search_keeps_keyword_across_pages_and_rejects_overlong_keyword(): void
    {
        $this->actingAs(User::factory()->create());
        GameAccount::factory()->count(16)->create();

        $response = $this->get('/admin/accounts?q=Mythic');

        $response->assertOk()->assertSee('page=2', false)->assertSee('q=Mythic', false);
        $this->assertCount(15, $response->viewData('accounts'));
        $this->get('/admin/accounts?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
    }

    public function test_joki_order_form_appears_only_when_a_whatsapp_number_is_set(): void
    {
        $this->get('/joki-ml')->assertOk()->assertSee('Joki Mobile Legends')->assertSee('Rp 6.000')->assertSee('Kontak WhatsApp belum diatur')->assertDontSee('data-joki-form', false)->assertDontSee('wa.me/', false);

        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);

        $this->get('/joki-ml')->assertOk()->assertDontSee('Kontak WhatsApp belum diatur')
            ->assertSee('data-whatsapp-url="https://wa.me/6281234567890"', false)
            ->assertSee('href="#pesan" data-joki-rank="Epic"', false)
            ->assertSee('data-price="6000"', false)
            ->assertSee('Masukkan Data Akun')->assertSee('Request Hero')
            ->assertSee('type="password"', false);
    }

    public function test_joki_order_form_cannot_submit_account_data_to_the_server(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);

        $html = $this->get('/joki-ml')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<form[^>]*data-joki-form[^>]*(action|method)=/', $html);
        $this->assertStringContainsString('<button type="button" class="btn" data-joki-submit>', $html);
        $this->assertSame(0, preg_match('/<button(?![^>]*type="button")[^>]*data-joki/', $html));
    }

    public function test_about_and_profile_pages_are_public_and_linked_from_navigation(): void
    {
        $this->get('/')->assertOk()->assertSee(route('about'), false)->assertSee(route('profile'), false);
        $this->get('/tentang')->assertOk()->assertSee('Tentang kami')->assertDontSee('wa.me/', false);
        $this->get('/profil')->assertOk()->assertSee('Profil saya')->assertSee('hanya tersimpan di browser ini')->assertSee('data-profile-form', false);
        $this->assertGuest();

        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);

        $this->get('/tentang')->assertOk()->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_removed_optional_features_are_not_publicly_reachable(): void
    {
        foreach (['/tukar-tambah', '/admin/finance', '/admin/trade-requests'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
