<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\TradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TradeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_submission_accepts_five_large_screenshots_and_optional_fields(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->postJson('/tukar-tambah', [
            'name' => 'Pengaju upload besar', 'email' => 'upload@example.com',
            'whatsapp_number' => '6281234567890',
            'category_id' => Category::factory()->create()->id,
            'description' => 'Akun Mythic lengkap dengan skin dan hero.',
            'screenshots' => collect(range(1, 5))->map(fn ($i) => UploadedFile::fake()->createWithContent("ss$i.png", $png)->size(4096))->all(),
            'status' => 'accepted', 'admin_note' => 'Tidak boleh tersimpan',
        ])->assertCreated()->assertJsonStructure(['message']);
        $request = TradeRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->admin_note);
        $this->assertNull($request->estimated_price);
        $this->assertCount(5, $request->images);
    }

    public function test_oversized_file_returns_validation_error_without_storing_request(): void
    {
        Storage::fake('public');
        $this->postJson('/tukar-tambah', ['screenshots' => [UploadedFile::fake()->create('huge.png', 4097, 'image/png')]])
            ->assertUnprocessable()->assertJsonValidationErrors('screenshots.0');
        $this->assertDatabaseCount('trade_requests', 0);
    }

    public function test_oversized_body_has_friendly_html_and_json_responses(): void
    {
        $this->withServerVariables(['CONTENT_LENGTH' => 1024 * 1024 * 1024])->post('/tukar-tambah')
            ->assertStatus(413)->assertSee('Upload terlalu besar')->assertDontSee('PostTooLargeException');
        $this->withServerVariables(['CONTENT_LENGTH' => 1024 * 1024 * 1024])->postJson('/tukar-tambah')
            ->assertStatus(413)->assertJsonStructure(['message']);
    }

    public function test_public_trade_form_can_submit_request_with_screenshots(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create(['name' => 'Mobile Legends']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');

        $this->get('/tukar-tambah')->assertOk()->assertSee('Ajukan tukar tambah akun game')->assertSee('Mobile Legends');

        $this->post('/tukar-tambah', [
            'name' => 'Budi Player',
            'whatsapp_number' => '6281234567890',
            'email' => 'budi@example.com',
            'category_id' => $category->id,
            'description' => 'Rank Mythic, level 60, skin 150, server Indonesia.',
            'estimated_price' => 350000,
            'desired_account' => 'Akun Mythic koleksi skin epic',
            'screenshots' => [UploadedFile::fake()->createWithContent('ss.png', $png)],
        ])->assertSessionHasNoErrors()->assertRedirect('/tukar-tambah');

        $tradeRequest = TradeRequest::with('images')->firstOrFail();
        $this->assertSame('pending', $tradeRequest->status);
        $this->assertSame(350000, $tradeRequest->estimated_price);
        $this->assertCount(1, $tradeRequest->images);
        Storage::disk('public')->assertExists($tradeRequest->images->first()->image_path);
    }

    public function test_trade_request_requires_valid_data_and_limits_screenshots(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');

        $this->post('/tukar-tambah', [
            'name' => 'Budi',
            'whatsapp_number' => '+62 invalid',
            'email' => 'not-email',
            'category_id' => $category->id,
            'description' => 'terlalu pendek',
        ])->assertSessionHasErrors(['whatsapp_number', 'email', 'description', 'screenshots']);

        $this->post('/tukar-tambah', [
            'name' => 'Budi',
            'whatsapp_number' => '6281234567890',
            'email' => 'budi@example.com',
            'category_id' => $category->id,
            'description' => 'Rank Mythic, level 60, skin 150, server Indonesia.',
            'screenshots' => collect(range(1, 6))->map(fn (int $index) => UploadedFile::fake()->createWithContent('ss'.$index.'.png', $png))->all(),
        ])->assertSessionHasErrors('screenshots');

        $this->assertDatabaseCount('trade_requests', 0);
    }

    public function test_admin_can_review_update_contact_and_delete_trade_request(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tradeRequest = TradeRequest::create([
            'category_id' => $category->id,
            'name' => 'Budi Player',
            'whatsapp_number' => '6281234567890',
            'email' => 'budi@example.com',
            'description' => 'Rank Mythic, level 60, skin 150, server Indonesia.',
            'estimated_price' => 350000,
            'desired_account' => 'Akun ML kolektor skin',
            'status' => 'pending',
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $tradeRequest->images()->create(['image_path' => UploadedFile::fake()->createWithContent('ss.png', $png)->store('trade-requests', 'public')]);

        $this->get('/admin/trade-requests')->assertRedirect('/admin/login');
        $this->actingAs($user)->get('/admin/trade-requests')->assertOk()->assertSee('Budi Player');
        $this->get('/admin/trade-requests/'.$tradeRequest->id)->assertOk()->assertSee('6281234567890')->assertSee('Hubungi via WhatsApp');

        $this->put('/admin/trade-requests/'.$tradeRequest->id, ['status' => 'processing', 'admin_note' => 'Minta screenshot tambahan'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('trade_requests', ['id' => $tradeRequest->id, 'status' => 'processing', 'admin_note' => 'Minta screenshot tambahan']);

        $path = $tradeRequest->images()->firstOrFail()->image_path;
        Storage::disk('public')->assertExists($path);
        $this->delete('/admin/trade-requests/'.$tradeRequest->id)->assertRedirect('/admin/trade-requests');
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('trade_requests', ['id' => $tradeRequest->id]);
    }

    public function test_dashboard_counts_pending_trade_requests(): void
    {
        $this->actingAs(User::factory()->create());
        TradeRequest::create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Budi Player',
            'whatsapp_number' => '6281234567890',
            'email' => 'budi@example.com',
            'description' => 'Rank Mythic, level 60, skin 150, server Indonesia.',
            'status' => 'pending',
        ]);

        $this->get('/admin')->assertOk()->assertSee('Tukar tambah pending')->assertSee('1');
    }

    public function test_category_used_by_trade_request_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();
        $tradeRequest = TradeRequest::create([
            'category_id' => $category->id, 'name' => 'Pelanggan tukar tambah',
            'whatsapp_number' => '6281234567890', 'email' => 'pelanggan@example.com',
            'description' => 'Akun pelanggan masih menunggu peninjauan.', 'status' => 'pending',
        ]);

        $this->delete('/admin/categories/'.$category->id)->assertSessionHasErrors('category');

        $this->assertModelExists($category);
        $this->assertModelExists($tradeRequest);
    }
}
