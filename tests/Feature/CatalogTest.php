<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminBannerController;
use App\Models\Banner;
use App\Models\Category;
use App\Models\ContactLink;
use App\Models\FeedbackMessage;
use App\Models\GameAccount;
use App\Models\InfoPost;
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
            $this->get('/katalog?'.http_build_query(['q' => $keyword, 'category' => $category->slug]))->assertOk()->assertSee($account->title)->assertDontSee('Rahasia draft')->assertDontSee('Tidak cocok');
        }
        $this->get('/akun/'.$draft->slug)->assertNotFound();
        $this->get('/kategori/mobile-legends')->assertOk()->assertSee($account->title);
        $this->get('/katalog?q=unknown-keyword')->assertOk()->assertSee('Belum ada akun yang cocok');
    }

    public function test_whatsapp_message_is_encoded_and_sold_accounts_have_no_contact_button(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $account = GameAccount::factory()->create(['title' => 'Mythic & Hero']);
        $this->assertSame('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya tertarik dengan akun Mythic & Hero seharga Rp 350.000. Apakah masih tersedia?'), $account->whatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Tanya Stok');
        $account->update(['status' => 'sold']);
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('Tanya Stok')->assertSee('sudah terjual');
        $this->get('/katalog?available=1')->assertDontSee('Mythic &amp; Hero', false);
    }

    public function test_sold_accounts_are_marked_and_hide_their_price_in_every_public_view(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $sold = GameAccount::factory()->create(['title' => 'Akun laku', 'price' => 777777, 'status' => 'sold']);
        $available = GameAccount::factory()->create(['title' => 'Akun ready', 'price' => 555555]);

        $this->get('/')->assertOk()->assertSee('Akun laku')->assertSee('Sudah terjual')->assertDontSee('Rp 777.777')->assertSee('Rp 555.555');
        $this->get('/akun/'.$sold->slug)->assertOk()->assertSee('sudah terjual')->assertSee('Lihat akun lain')->assertDontSee('Rp 777.777')->assertDontSee('Tukar tambah');
        $this->get('/akun/'.$available->slug)->assertOk()->assertSee('Rp 555.555')->assertDontSee('Lihat akun lain');

        $html = $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$sold->id, $available->id]]))->assertOk()->json('html');

        $this->assertStringNotContainsString('Rp 777.777', $html);
        $this->assertStringContainsString('Rp 555.555', $html);
        $this->assertStringContainsString('Terjual', $html);
    }

    public function test_discounted_accounts_show_the_struck_price_and_percentage(): void
    {
        $discounted = GameAccount::factory()->create(['title' => 'Akun diskon', 'price' => 80000, 'original_price' => 100000]);
        $plain = GameAccount::factory()->create(['title' => 'Akun biasa', 'price' => 90000, 'original_price' => 90000]);

        $this->get('/')->assertOk()->assertSee('Rp 100.000')->assertSee('Hemat Rp 20.000')->assertSee('Rp 80.000');
        $this->get('/akun/'.$discounted->slug)->assertOk()->assertSee('line-through', false)->assertSee('Rp 100.000')->assertSee('-20%');
        $this->get('/akun/'.$plain->slug)->assertOk()->assertDontSee('(-', false)->assertSee('Rekomendasi Produk');
        $this->assertStringContainsString('Rp 100.000', $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$discounted->id]]))->json('html'));

        $discounted->update(['status' => 'sold']);

        $this->get('/akun/'.$discounted->slug)->assertOk()->assertDontSee('Rp 100.000')->assertDontSee('Rp 80.000');
    }

    public function test_flash_sale_page_lists_only_running_flash_sales_of_available_accounts(): void
    {
        $this->travelTo('2026-11-11 05:00:00');
        GameAccount::factory()->create(['title' => 'Flash berjalan', 'flash_sale_ends_at' => '2026-11-11 10:04:03']);
        GameAccount::factory()->create(['title' => 'Flash selesai', 'flash_sale_ends_at' => '2026-11-11 04:59:59']);
        GameAccount::factory()->create(['title' => 'Flash terjual', 'status' => 'sold', 'flash_sale_ends_at' => '2026-11-12 00:00:00']);
        GameAccount::factory()->create(['title' => 'Bukan flash']);

        $this->get('/flash-sale')->assertOk()->assertSee('Flash berjalan')->assertSee('05:04:03')->assertSee('data-countdown="2026-11-11T10:04:03Z"', false)
            ->assertDontSee('Flash selesai')->assertDontSee('Flash terjual')->assertDontSee('Bukan flash');
        $this->get('/')->assertOk()->assertSee('Flash Sale')->assertSee('1 akun')->assertSee(route('flash-sale'), false);

        $this->travelTo('2026-11-11 10:04:04');

        $this->get('/flash-sale')->assertOk()->assertSee('Belum ada flash sale saat ini')->assertDontSee('Flash berjalan');
        $this->get('/')->assertOk()->assertDontSee(route('flash-sale'), false);
    }

    public function test_admin_sets_discount_and_flash_sale_in_jakarta_time_and_can_clear_them(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create(['price' => 80000]);
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'price' => 80000, 'description' => 'Deskripsi', 'specifications' => 'Rank Mythic', 'status' => 'available'];

        $this->put('/admin/accounts/'.$account->id, $data + ['original_price' => 100000, 'flash_sale_ends_at' => '2026-11-12T21:00'])->assertSessionHasNoErrors();
        $account->refresh();
        $this->assertSame(100000, $account->original_price);
        $this->assertSame('2026-11-12 14:00:00', $account->flash_sale_ends_at->utc()->format('Y-m-d H:i:s'));
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('value="2026-11-12T21:00"', false);

        $this->put('/admin/accounts/'.$account->id, $data + ['original_price' => 70000])->assertSessionHasErrors(['original_price' => 'Harga coret harus lebih besar dari harga jual.']);
        $this->put('/admin/accounts/'.$account->id, $data + ['flash_sale_ends_at' => 'besok'])->assertSessionHasErrors('flash_sale_ends_at');

        $this->put('/admin/accounts/'.$account->id, $data + ['original_price' => '', 'flash_sale_ends_at' => ''])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('game_accounts', ['id' => $account->id, 'original_price' => null, 'flash_sale_ends_at' => null]);
    }

    public function test_rental_accounts_stay_out_of_the_sale_catalog_and_appear_on_the_rental_page(): void
    {
        $rental = GameAccount::factory()->create(['title' => 'Akun sewa FF', 'is_rental' => true]);
        $rental->rentalPackages()->createMany([['duration' => '1 Hari', 'price' => 60000, 'sort_order' => 1], ['duration' => '6 Jam', 'price' => 25000, 'sort_order' => 0]]);
        $rented = GameAccount::factory()->create(['title' => 'Akun lagi disewa', 'is_rental' => true, 'status' => 'rented', 'ready_estimate_at' => '2026-11-12 13:30:00']);
        $rented->rentalPackages()->create(['duration' => '3 Jam', 'price' => 15000]);
        GameAccount::factory()->create(['title' => 'Akun sewa draft', 'is_rental' => true, 'status' => 'draft']);
        $sale = GameAccount::factory()->create(['title' => 'Akun jual biasa']);

        $this->get('/katalog')->assertOk()->assertSee('Akun jual biasa')->assertDontSee('Akun sewa FF');
        $this->get('/')->assertOk()->assertSee('Rental Akun')->assertSeeInOrder(['Akun sewa FF', 'Akun lagi disewa'])->assertDontSee('Akun sewa draft');
        $this->get('/rental')->assertOk()->assertSeeInOrder(['Akun sewa FF', 'Akun lagi disewa'])->assertSee('Rp 25.000')->assertSee('6 Jam')
            ->assertSee('Ready ± 12 Nov 20:30 WIB')->assertDontSee('Akun sewa draft')->assertDontSee('Akun jual biasa');
        $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$rental->id, $sale->id]]))->assertOk()->assertJsonPath('ids', [$sale->id]);
    }

    public function test_rental_detail_lists_packages_and_sends_rental_orders_to_the_rental_number(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $rental = GameAccount::factory()->create(['title' => 'Akun sewa FF', 'is_rental' => true]);
        $package = $rental->rentalPackages()->create(['duration' => '6 Jam', 'price' => 25000]);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya ingin menyewa akun Akun sewa FF paket 6 Jam seharga Rp 25.000.'), $rental->rentalWhatsappUrl($package));

        Setting::create(['key' => 'whatsapp_rental_number', 'value' => '6287777777777']);

        $this->get('/akun/'.$rental->slug)->assertOk()->assertSee('Siap disewa')->assertSee('Paket sewa')->assertSee('https://wa.me/6287777777777?text=', false)
            ->assertDontSee('Tukar tambah')->assertDontSee('>Cicilan<', false)->assertDontSee('Simpan ke wishlist');

        $rental->update(['status' => 'rented', 'ready_estimate_at' => '2026-11-12 13:30:00']);

        $this->assertNull($rental->rentalWhatsappUrl($package));
        $this->get('/akun/'.$rental->slug)->assertOk()->assertSee('Sedang disewa')->assertSee('estimasi ready 12 Nov 2026 20:30 WIB')
            ->assertSee('Tanya jadwal sewa')->assertDontSee('aria-label="Sewa 6 Jam"', false);
    }

    /** A real grayscale PNG of the given size, built without GD so the dimensions rule can read it. */
    private function pngOfSize(int $width, int $height): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $rows = str_repeat("\0".str_repeat("\x80", $width), $height);

        return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 0, 0, 0, 0)).$chunk('IDAT', gzcompress($rows)).$chunk('IEND', '');
    }

    public function test_admin_manages_several_home_banners_shown_as_a_slider(): void
    {
        Storage::fake('public');
        $this->get('/')->assertSee('images/zuto-banner.webp', false)->assertDontSee('data-slider ', false);
        $this->get('/admin/banner')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->buyer()->create())->get('/admin/banner')->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->get('/admin/banner')->assertOk()->assertSee('Tambah banner')->assertSee('Banner aktif (0)')->assertSee('banner bawaan');
        $this->post('/admin/banner', ['image' => UploadedFile::fake()->createWithContent('kecil.png', $this->pngOfSize(300, 100))])->assertSessionHasErrors('image');
        $this->post('/admin/banner', [])->assertSessionHasErrors('image');

        $this->post('/admin/banner', ['image' => UploadedFile::fake()->createWithContent('a.png', $this->pngOfSize(1500, 500)), 'link_url' => 'https://zutomarket.id/flash-sale', 'sort_order' => 2])->assertSessionHasNoErrors();
        $one = Banner::sole();
        $this->get('/')->assertSee($one->imageUrl(), false)->assertDontSee('images/zuto-banner.webp', false)->assertDontSee('data-slider-dot', false)
            ->assertSee('href="https://zutomarket.id/flash-sale" class="block"', false);

        $this->post('/admin/banner', ['image' => UploadedFile::fake()->createWithContent('b.png', $this->pngOfSize(1280, 430)), 'sort_order' => 1])->assertSessionHasNoErrors();
        $two = Banner::where('sort_order', 1)->sole();
        $this->get('/')->assertSee('data-slider', false)->assertSeeInOrder([$two->imageUrl(), $one->imageUrl(), 'data-slider-dot="0"', 'data-slider-dot="1"'], false)
            ->assertSee('aria-label="1 dari 2"', false);
        $this->get('/admin/banner')->assertSee('Banner aktif (2)');

        $oldPath = $one->image_path;
        $this->put('/admin/banner/'.$one->id, ['image' => UploadedFile::fake()->createWithContent('c.png', $this->pngOfSize(1280, 430)), 'link_url' => '', 'sort_order' => 0])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNull($one->fresh()->link_url);
        $this->get('/')->assertSeeInOrder([$one->fresh()->imageUrl(), $two->imageUrl()], false);
        $this->put('/admin/banner/'.$one->id, ['link_url' => 'http://tidak-aman.example'])->assertSessionHasErrors('link_url');

        $this->delete('/admin/banner/'.$two->id)->assertSessionHas('success');
        Storage::disk('public')->assertMissing($two->image_path);
        $this->delete('/admin/banner/'.$one->id);
        $this->get('/')->assertSee('images/zuto-banner.webp', false);

        Banner::insert(array_fill(0, AdminBannerController::MAX_BANNERS, ['image_path' => 'banner/x.png', 'width' => 1280, 'height' => 430, 'sort_order' => 0]));
        $this->post('/admin/banner', ['image' => UploadedFile::fake()->createWithContent('d.png', $this->pngOfSize(1280, 430))])->assertSessionHasErrors('image');
    }

    public function test_the_banner_migration_moves_the_old_single_banner_into_the_list(): void
    {
        $migration = require database_path('migrations/2026_11_12_202721_create_banners_table.php');
        $migration->down();
        Setting::insert([['key' => 'banner_image', 'value' => 'banner/lama.jpg'], ['key' => 'banner_size', 'value' => '3200x1074'], ['key' => 'banner_link', 'value' => '']]);
        $migration->up();

        $banner = Banner::sole();
        $this->assertSame(['banner/lama.jpg', 3200, 1074, null], [$banner->image_path, $banner->width, $banner->height, $banner->link_url]);
        $this->assertSame(0, Setting::where('key', 'like', 'banner%')->count());
    }

    public function test_a_rental_category_shows_its_rental_accounts_and_counts_them(): void
    {
        $rentalCategory = Category::factory()->create(['name' => 'JASA RENTAL AKUN', 'slug' => 'rental-akun']);
        $saleCategory = Category::factory()->create(['name' => 'STOK FF', 'slug' => 'stok-ff']);
        $ready = GameAccount::factory()->for($rentalCategory)->create(['title' => 'Sewa Mythic Glory', 'is_rental' => true]);
        $ready->rentalPackages()->create(['duration' => '6 Jam', 'price' => 25000]);
        GameAccount::factory()->for($rentalCategory)->create(['title' => 'Sewa lagi dipakai', 'is_rental' => true, 'status' => 'rented'])->rentalPackages()->create(['duration' => '3 Jam', 'price' => 15000]);
        GameAccount::factory()->for($rentalCategory)->create(['title' => 'Sewa draft', 'is_rental' => true, 'status' => 'draft']);
        GameAccount::factory()->for($saleCategory)->create(['title' => 'Akun FF jual']);

        $this->get('/')->assertOk()->assertSeeInOrder(['JASA RENTAL AKUN', '1 akun tersedia']);
        $this->get('/kategori/rental-akun')->assertOk()->assertSee('Sewa Mythic Glory')->assertSee('Siap disewa')->assertSee('6 Jam')
            ->assertSee('Sewa lagi dipakai')->assertDontSee('Sewa draft')->assertDontSee('Akun FF jual')->assertSee('2 produk');
        $this->get('/kategori/rental-akun?status=available')->assertSee('Sewa Mythic Glory')->assertDontSee('Sewa lagi dipakai');
        $this->get('/kategori/stok-ff')->assertSee('Akun FF jual')->assertDontSee('Sewa Mythic Glory');
        $this->get('/katalog')->assertSee('Akun FF jual')->assertDontSee('Sewa Mythic Glory');
    }

    public function test_admin_price_fields_accept_thousand_dots(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'description' => 'Deskripsi', 'specifications' => 'Rank Mythic', 'status' => 'available'];

        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('name="price" type="text" inputmode="numeric"', false)->assertSee('data-money', false);
        $this->put('/admin/accounts/'.$account->id, $data + ['price' => '1.000.000', 'original_price' => 'Rp 1.250.000'])->assertSessionHasNoErrors();
        $this->assertSame(1000000, $account->fresh()->price);
        $this->assertSame(1250000, $account->fresh()->original_price);
        $this->put('/admin/accounts/'.$account->id, $data + ['price' => '350000'])->assertSessionHasNoErrors();
        $this->assertSame(350000, $account->fresh()->price);
        foreach (['12.5', '1.00.000', 'satu juta', '-5.000'] as $wrong) {
            $this->put('/admin/accounts/'.$account->id, $data + ['price' => $wrong])->assertSessionHasErrors('price');
        }
        $this->assertSame(350000, $account->fresh()->price);

        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 1, 'packages' => [['duration' => '1 Hari', 'price' => '60.000']]])->assertSessionHasNoErrors();
        $this->assertSame([60000], $account->fresh()->rentalPackages->pluck('price')->all());

        $this->get('/admin/sales')->assertOk()->assertSee('name="amount" type="text" inputmode="numeric"', false);
        $this->post('/admin/sales', ['type' => 'joki', 'item' => 'Joki Mythic', 'amount' => '1.250.000', 'sold_on' => '2026-11-12'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('manual_sales', ['item' => 'Joki Mythic', 'amount' => 1250000]);
    }

    public function test_admin_saves_rental_packages_and_rejects_invalid_rental_input(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'description' => 'Deskripsi', 'specifications' => 'Rank Mythic', 'status' => 'available'];

        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 1, 'packages' => [['duration' => '1 Hari', 'price' => 60000], ['duration' => '6 Jam', 'price' => 25000], ['duration' => '', 'price' => '']]])->assertSessionHasNoErrors();
        $account->refresh();
        $this->assertTrue($account->is_rental);
        $this->assertSame(25000, $account->price);
        $this->assertSame(['1 Hari', '6 Jam'], $account->rentalPackages->pluck('duration')->all());
        $this->get('/admin/accounts/'.$account->id.'/edit')->assertOk()->assertSee('value="1 Hari"', false);
        $this->get('/admin/rentals')->assertOk()->assertSee($account->title)->assertSee('1 Hari Rp 60.000');

        $this->put('/admin/accounts/'.$account->id, array_merge($data, ['is_rental' => 1, 'status' => 'rented', 'packages' => [['duration' => '3 Jam', 'price' => 15000]], 'ready_estimate_at' => '2026-11-12T20:30']))->assertSessionHasNoErrors();
        $this->assertSame('rented', $account->fresh()->status);
        $this->assertSame('2026-11-12 13:30:00', $account->fresh()->ready_estimate_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(['3 Jam'], $account->fresh()->rentalPackages->pluck('duration')->all());

        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 1, 'packages' => [['duration' => '', 'price' => '']]])->assertSessionHasErrors(['packages' => 'Isi minimal satu paket sewa untuk akun rental.']);
        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 1, 'packages' => [['duration' => '6 Jam', 'price' => '']]])->assertSessionHasErrors('packages.0.price');
        $this->put('/admin/accounts/'.$account->id, array_merge($data, ['is_rental' => 0, 'price' => 100000, 'status' => 'rented']))->assertSessionHasErrors(['status' => 'Status "Sedang disewa" hanya untuk akun rental.']);

        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 0, 'price' => 100000])->assertSessionHasNoErrors();
        $this->assertFalse($account->fresh()->is_rental);
        $this->assertSame(0, $account->fresh()->rentalPackages()->count());
        $this->assertNull($account->fresh()->ready_estimate_at);
        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 0])->assertSessionHasErrors('price');
    }

    public function test_admin_rental_menu_lists_only_rentals_and_toggles_their_status(): void
    {
        $this->get('/admin/rentals')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $rental = GameAccount::factory()->create(['title' => 'Akun sewa FF', 'is_rental' => true]);
        $rental->rentalPackages()->create(['duration' => '6 Jam', 'price' => 25000]);
        $sale = GameAccount::factory()->create(['title' => 'Akun jual ML']);

        $this->get('/admin/rentals')->assertOk()->assertSee('Akun sewa FF')->assertSee('6 Jam Rp 25.000')->assertDontSee('Akun jual ML')->assertSee('1 siap disewa · 0 sedang disewa')
            ->assertSee(route('admin.accounts.create', ['rental' => 1]), false);
        $this->get('/admin/accounts')->assertOk()->assertSee('Akun jual ML')->assertDontSee('Akun sewa FF');
        $this->get('/admin/accounts/create?rental=1')->assertOk()->assertSee('Tambah akun rental')->assertSee('<input type="hidden" name="is_rental" value="1">', false)
            ->assertSee('Paket sewa')->assertSee('value="rented"', false)->assertDontSee('id="price"', false)->assertDontSee('value="sold"', false);
        $this->get('/admin/accounts/create')->assertOk()->assertSee('Tambah akun game')->assertSee('<input type="hidden" name="is_rental" value="0">', false)
            ->assertDontSee('Paket sewa')->assertDontSee('Estimasi ready')->assertDontSee('value="rented"', false)->assertSee('id="price"', false);
        $this->get('/admin/accounts/'.$rental->id.'/edit')->assertOk()->assertSee('Edit akun rental')->assertSee('value="6 Jam"', false);
        $this->get('/admin/accounts/'.$sale->id.'/edit')->assertOk()->assertSee('Edit akun game')->assertDontSee('Paket sewa');

        $this->patch('/admin/rentals/'.$rental->id.'/status', ['status' => 'rented', 'ready_estimate_at' => '2026-11-12T20:30'])->assertRedirect()->assertSessionHas('success', 'Akun ditandai sedang disewa.');
        $this->assertSame('rented', $rental->fresh()->status);
        $this->assertSame('2026-11-12 13:30:00', $rental->fresh()->ready_estimate_at->utc()->format('Y-m-d H:i:s'));

        $this->patch('/admin/rentals/'.$rental->id.'/status', ['status' => 'available'])->assertRedirect();
        $this->assertSame('available', $rental->fresh()->status);
        $this->assertNull($rental->fresh()->ready_estimate_at);

        $this->patch('/admin/rentals/'.$rental->id.'/status', ['status' => 'sold'])->assertSessionHasErrors('status');
        $this->patch('/admin/rentals/'.$sale->id.'/status', ['status' => 'rented'])->assertNotFound();
        $this->assertSame('available', $sale->fresh()->status);
    }

    public function test_saving_a_rental_account_returns_to_the_rental_menu(): void
    {
        $this->actingAs(User::factory()->create());
        $account = GameAccount::factory()->create();
        $data = ['title' => $account->title, 'category_id' => $account->category_id, 'description' => 'Deskripsi', 'specifications' => 'Rank', 'status' => 'available'];

        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 1, 'packages' => [['duration' => '1 Hari', 'price' => 60000]]])->assertRedirect('/admin/rentals');
        $this->put('/admin/accounts/'.$account->id, $data + ['is_rental' => 0, 'price' => 100000])->assertRedirect('/admin/accounts');
    }

    public function test_trade_in_button_opens_whatsapp_only_for_available_accounts(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $account = GameAccount::factory()->create(['title' => 'Mythic & Hero']);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya ingin mengajukan tukar tambah untuk akun Mythic & Hero seharga Rp 350.000.'), $account->tradeInWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Tukar tambah');

        $account->update(['status' => 'sold']);

        $this->assertNull($account->tradeInWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('Tukar tambah');
    }

    public function test_installment_button_asks_for_terms_and_follows_the_seller_number(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $account = GameAccount::factory()->create(['title' => 'Mythic & Hero']);
        $sellerAccount = GameAccount::factory()->create(['seller_whatsapp' => '6289999999999']);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya ingin mengajukan cicilan untuk akun Mythic & Hero seharga Rp 350.000.'), $account->installmentWhatsappUrl());
        $this->assertStringEndsWith(rawurlencode('Mohon info DP dan tenor yang tersedia.'), $account->installmentWhatsappUrl());
        $this->assertStringStartsWith('https://wa.me/6289999999999?text=', $sellerAccount->installmentWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('>Cicilan</a>', false);

        $account->update(['status' => 'sold']);

        $this->assertNull($account->installmentWhatsappUrl());
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('>Cicilan</a>', false);
    }

    public function test_account_with_seller_number_contacts_the_seller_instead_of_the_store(): void
    {
        $sellerAccount = GameAccount::factory()->create(['seller_whatsapp' => '6289999999999']);
        $storeAccount = GameAccount::factory()->create();

        $this->assertStringStartsWith('https://wa.me/6289999999999?text=', $sellerAccount->whatsappUrl());
        $this->assertNull($storeAccount->whatsappUrl());
        $this->get('/akun/'.$sellerAccount->slug)->assertOk()->assertSee('Tanya Stok ke penjual')->assertSee('https://wa.me/6289999999999?text=', false);

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
        $this->get('/admin/sales?month=2026-09')->assertOk()->assertSee('Belum ada penjualan pada bulan ini');
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

        $this->post('/admin/sales', ['type' => 'joki', 'item' => 'Joki Legend 5 bintang', 'amount' => 60000, 'sold_on' => '2026-10-31', 'note' => 'Budi'])
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

        $this->post('/admin/sales', ['amount' => 0, 'sold_on' => 'kemarin'])->assertSessionHasErrors(['type', 'item', 'amount', 'sold_on']);
        $this->post('/admin/sales', ['type' => 'lainnya', 'item' => 'X', 'amount' => 1000, 'sold_on' => '2026-10-01'])->assertSessionHasErrors('type');
        $this->assertDatabaseCount('manual_sales', 0);
    }

    public function test_sales_page_breaks_down_types_and_lists_one_table_newest_first(): void
    {
        $this->actingAs(User::factory()->create());
        GameAccount::factory()->create(['title' => 'Akun katalog', 'status' => 'sold', 'price' => 300000])->forceFill(['sold_at' => '2026-10-05 10:00:00'])->save();
        ManualSale::factory()->create(['type' => 'joki', 'item' => 'Joki Epic V – Mythic', 'amount' => 325000, 'sold_on' => '2026-10-20']);
        ManualSale::factory()->create(['type' => 'rental', 'item' => 'Sewa FF 1 hari', 'amount' => 60000, 'sold_on' => '2026-10-12']);
        ManualSale::factory()->create(['type' => 'jual_akun', 'item' => 'Akun luar katalog', 'amount' => 150000, 'sold_on' => '2026-10-08']);
        ManualSale::factory()->create(['type' => 'rekber', 'item' => 'Fee rekber', 'amount' => 25000, 'sold_on' => '2026-10-01']);
        ManualSale::factory()->create(['type' => 'lainnya', 'item' => 'Catatan lama', 'amount' => 10000, 'sold_on' => '2026-10-02']);

        $response = $this->get('/admin/sales?month=2026-10');

        $response->assertOk()->assertSeeInOrder(['Joki Epic V – Mythic', 'Sewa FF 1 hari', 'Akun luar katalog', 'Akun katalog', 'Catatan lama', 'Fee rekber'])
            ->assertSee('Katalog')->assertSee('Rekber');
        $breakdown = $response->viewData('breakdown');
        $this->assertSame(['count' => 1, 'amount' => 325000], array_intersect_key($breakdown['joki'], ['count' => 0, 'amount' => 0]));
        $this->assertSame(['count' => 2, 'amount' => 450000], array_intersect_key($breakdown['jual_akun'], ['count' => 0, 'amount' => 0]));
        $this->assertSame(['count' => 1, 'amount' => 10000], array_intersect_key($breakdown['lainnya'], ['count' => 0, 'amount' => 0]));
        $this->assertSame(6, $response->viewData('monthCount'));
        $this->assertSame(870000, $response->viewData('monthRevenue'));
        $this->assertSame(1, substr_count($response->getContent(), 'aria-label="Hapus Fee rekber"'));
        $this->assertSame(0, substr_count($response->getContent(), 'aria-label="Hapus Akun katalog"'));
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

    public function test_admin_can_delete_several_accounts_with_their_images_at_once(): void
    {
        Storage::fake('public');
        $this->delete('/admin/accounts', ['ids' => [1]])->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $first = GameAccount::factory()->create(['main_image' => 'accounts/first.png']);
        $second = GameAccount::factory()->create(['main_image' => 'images/zuto-bg1.webp']);
        $kept = GameAccount::factory()->create();
        Storage::disk('public')->put('accounts/first.png', 'x');
        Storage::disk('public')->put('accounts/gallery.png', 'x');
        $first->images()->create(['image_path' => 'accounts/gallery.png']);

        $this->get('/admin/accounts')->assertOk()->assertSee('data-bulk-delete', false)->assertSee('name="ids[]" value="'.$first->id.'"', false);
        $this->get('/admin')->assertOk()->assertDontSee('data-bulk-item', false);

        $this->delete('/admin/accounts', ['ids' => [$first->id, $second->id, $first->id]])->assertRedirect()->assertSessionHas('success', '2 akun berhasil dihapus.');

        $this->assertDatabaseMissing('game_accounts', ['id' => $first->id]);
        $this->assertDatabaseMissing('game_accounts', ['id' => $second->id]);
        $this->assertDatabaseHas('game_accounts', ['id' => $kept->id]);
        Storage::disk('public')->assertMissing('accounts/first.png');
        Storage::disk('public')->assertMissing('accounts/gallery.png');
        $this->assertFileExists(public_path('images/zuto-bg1.webp'));

        $this->delete('/admin/accounts', [])->assertSessionHasErrors(['ids' => 'Pilih minimal satu akun untuk dihapus.']);
        $this->assertDatabaseHas('game_accounts', ['id' => $kept->id]);
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
        $response = $this->get('/katalog?'.http_build_query(['q' => 'Mythic', 'category' => $category->slug]));
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

    public function test_joki_page_lists_all_packages_with_prices(): void
    {
        $this->get('/joki-ml')->assertOk()->assertSee('Paket Joki')->assertDontSee('data-joki-package', false)
            ->assertSeeInOrder(['Paket Epic', 'Paket Legend', 'Paket Mythic', 'Paket Mythic Honor &amp; Glory'], false)
            ->assertSee('Epic V – Legend V')->assertSee('Rp 150.000')->assertSee('Legend V – Mythic Immortal')->assertSee('Rp 1.150.000')->assertSee('Glory – Immortal')->assertSee('20 paket');

        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);

        $html = $this->get('/joki-ml')->assertOk()->getContent();

        $this->assertSame(20, substr_count($html, 'data-joki-package="'));
        $this->assertStringContainsString('<option value="Honor – Glory" data-price="225000">', $html);
        $this->assertStringContainsString('<option value="">Per bintang (isi rank dan jumlah bintang)</option>', $html);
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
        $this->get('/tentang')->assertOk()->assertSee('Tentang kami')->assertSee('WA Admin')->assertSee('Instagram')->assertSee('TikTok')->assertSee('Saluran WhatsApp')
            ->assertSee('Belum ada kontak WA Admin')->assertDontSee('wa.me/', false);
        $this->get('/profil')->assertOk()->assertSee('>Masuk</h1>', false)->assertSee('Lupa password?')->assertSee('action="'.route('account.login').'"', false)->assertDontSee('hanya tersimpan di browser ini')->assertDontSee('data-profile-form', false)
            ->assertSee('WA Admin &amp; Sosmed', false)->assertSee(route('about'), false);
        $this->assertGuest();
    }

    public function test_about_page_lists_every_whatsapp_admin_in_sort_order_with_counts(): void
    {
        ContactLink::factory()->create(['title' => 'Admin Mobile Legends', 'value' => '6282222222222', 'sort_order' => 2]);
        ContactLink::factory()->create(['title' => 'Admin Free Fire', 'value' => '6281111111111', 'sort_order' => 1]);
        ContactLink::factory()->create(['title' => 'Admin Rental Akun', 'value' => '6283333333333', 'sort_order' => 3]);

        $response = $this->get('/tentang');

        $response->assertOk()->assertSeeInOrder(['Admin Free Fire', 'Admin Mobile Legends', 'Admin Rental Akun'])
            ->assertSee('https://wa.me/6283333333333', false)->assertDontSee('Belum ada kontak WA Admin');
        $this->assertMatchesRegularExpression('/WA Admin\s*<span[^>]*>3<\/span>/', $response->getContent());
    }

    public function test_admin_manages_contacts_and_values_are_validated_per_group(): void
    {
        $this->post('/admin/contacts', [])->assertRedirect('/admin/login');
        $this->get('/admin/contacts')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());

        $this->get('/admin/contacts')->assertOk()->assertSee('Kontak &amp; Sosmed', false)->assertSee('Instagram');
        $this->post('/admin/contacts', ['group' => 'admin', 'title' => 'Admin Free Fire', 'value' => '6281111111111', 'subtitle' => 'Khusus FF'])->assertSessionHasNoErrors();
        $contact = ContactLink::where('title', 'Admin Free Fire')->firstOrFail();
        $this->assertSame('https://wa.me/6281111111111', $contact->url());
        $this->assertSame('WA', $contact->badgeLabel());

        $this->post('/admin/contacts', ['group' => 'admin', 'title' => 'Salah', 'value' => '0812 3456'])->assertSessionHasErrors(['value' => 'Nomor WhatsApp harus diawali kode negara tanpa tanda +, contoh 6281234567890.']);
        $this->post('/admin/contacts', ['group' => 'social', 'title' => 'YouTube', 'value' => 'youtube.com/zuto'])->assertSessionHasErrors(['value' => 'Isi tautan lengkap yang diawali https://.']);
        $this->post('/admin/contacts', ['group' => 'lainnya', 'title' => 'X', 'value' => 'https://x.com'])->assertSessionHasErrors('group');
        $this->assertDatabaseMissing('contact_links', ['title' => 'Salah']);

        $this->put('/admin/contacts/'.$contact->id, ['group' => 'social', 'title' => 'YouTube Zuto', 'value' => 'https://www.youtube.com/@zuto', 'sort_order' => 5])->assertSessionHasNoErrors();
        $this->assertSame('YOUTUBE', $contact->fresh()->badgeLabel());
        $this->get('/tentang')->assertOk()->assertSee('YouTube Zuto')->assertSee('https://www.youtube.com/@zuto', false);

        $this->delete('/admin/contacts/'.$contact->id)->assertRedirect();
        $this->assertDatabaseMissing('contact_links', ['id' => $contact->id]);
    }

    public function test_store_pages_offer_the_installable_app_but_admin_pages_do_not(): void
    {
        $this->get('/')->assertOk()->assertSee('rel="manifest"', false)->assertSee('data-install-prompt', false)->assertSee('Pasang Aplikasi ZutoMarket');
        $this->get('/admin/login')->assertOk()->assertDontSee('data-install-prompt', false);

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['standalone', '/', '/'], [$manifest['display'], $manifest['start_url'], $manifest['scope']]);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));
        foreach ($manifest['icons'] as $icon) {
            [$width, $height] = getimagesize(public_path(ltrim($icon['src'], '/')));
            $this->assertSame($icon['sizes'], $width.'x'.$height);
        }
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_pages_share_the_logo_preview_image_and_favicon(): void
    {
        $account = GameAccount::factory()->create(['title' => 'Akun spesial']);

        $this->get('/')->assertOk()->assertSee('<meta property="og:image" content="'.asset('images/zuto-og.jpg').'">', false)
            ->assertSee('rel="icon" type="image/png" sizes="48x48"', false)->assertSee('Created By OzinVoid')->assertDontSee('aria-label="Ikuti', false)->assertSee('Sinyal Trust')->assertSee('Proses cepat setelah pembayaran')
            ->assertSee('<title>ZutoMarket · SHOPGAME ZUTO</title>', false)->assertSee('<meta property="og:title" content="ZutoMarket · SHOPGAME ZUTO">', false)->assertDontSee('ZutoShopGame');
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('<meta property="og:title" content="Akun spesial', false)
            ->assertSee('<meta property="og:url" content="'.route('accounts.show', $account->slug).'">', false);
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('images/zuto-og.jpg')), 0, 2));
        $this->assertFileExists(public_path('images/icons/favicon-48.png'));
        $this->assertFileExists(public_path('images/zuto-logo.webp'));
    }

    public function test_home_shows_limited_sections_with_see_all_links_only_for_available_and_flash_sale(): void
    {
        $this->travelTo('2026-11-11 05:00:00');
        GameAccount::factory()->count(8)->sequence(fn ($sequence) => ['title' => 'Tersedia '.$sequence->index, 'created_at' => now()->subMinutes(10 - $sequence->index)])->create();
        GameAccount::factory()->count(5)->sequence(fn ($sequence) => ['title' => 'Kilat '.$sequence->index, 'flash_sale_ends_at' => now()->addHours($sequence->index + 1)])->create();
        GameAccount::factory()->count(5)->sequence(fn ($sequence) => ['title' => 'Laku '.$sequence->index, 'status' => 'sold'])->create();
        GameAccount::factory()->count(5)->sequence(fn ($sequence) => ['title' => 'Sewa '.$sequence->index, 'is_rental' => true])->create();

        $response = $this->get('/');

        $response->assertOk()->assertSeeInOrder(['Flash Sale', 'Produk Terbaru', 'Rental Akun', 'Produk Terjual']);
        $this->assertCount(6, $response->viewData('available'));
        $this->assertCount(4, $response->viewData('flashSale'));
        $this->assertCount(4, $response->viewData('rentals'));
        $this->assertCount(4, $response->viewData('sold'));
        $response->assertDontSee('akun siap dibeli')->assertDontSee('akun · berakhir');
        $this->assertSame(['Kilat 0', 'Kilat 1', 'Kilat 2', 'Kilat 3'], $response->viewData('flashSale')->pluck('title')->all());
        $response->assertSee(route('catalog.all', ['available' => 1]), false)->assertSee(route('flash-sale'), false)->assertSee('Berakhir dalam');
        $response->assertSee('Jelajahi katalog')->assertSee('18 akun ditemukan')->assertSee('<form action="'.route('catalog.all').'#katalog" method="GET"', false)->assertSee('Cari game, rank, hero, skin, atau server...');
        $this->assertSame(2, substr_count($response->getContent(), 'Lihat ›'));
    }

    public function test_home_hides_empty_sections_and_sends_old_catalog_links_to_the_catalog_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada akun yang tersedia')->assertDontSee('Flash Sale ⚡')->assertDontSee('Produk Terjual')->assertDontSee('id="home-rental"', false);
        $this->get('/?q=mythic&page=2')->assertRedirect(route('catalog.all', ['q' => 'mythic', 'page' => 2]));
        $this->get('/katalog')->assertOk()->assertSee('Katalog akun');
    }

    public function test_catalog_page_has_chips_product_count_and_status_filter(): void
    {
        $category = Category::factory()->create(['name' => 'Mobile Legends']);
        GameAccount::factory()->for($category)->create(['title' => 'Akun siap jual']);
        GameAccount::factory()->for($category)->create(['title' => 'Akun laku keras', 'status' => 'sold']);

        $this->get('/katalog')->assertOk()
            ->assertSeeInOrder(['Home', 'Katalog', 'Semua', 'Mobile Legends', 'Produk Terjual', '2 produk', 'Filter', 'Semua status', 'Terbaru'])
            ->assertSee('Akun siap jual')->assertSee('Akun laku keras')->assertDontSee('Reset filter');
        $this->get('/katalog?status=sold')->assertOk()->assertSee('1 produk')->assertSee('Akun laku keras')->assertDontSee('Akun siap jual')
            ->assertSee('<option value="sold" selected', false)->assertSee('Reset filter');
        $this->get('/katalog?status=available')->assertOk()->assertSee('Akun siap jual')->assertDontSee('Akun laku keras');
        $this->get('/katalog?status=draft')->assertSessionHasErrors('status');
    }

    public function test_every_page_has_the_theme_toggle_and_sets_the_theme_before_styles_load(): void
    {
        foreach (['/', '/joki-ml', '/admin/login'] as $path) {
            $html = $this->get($path)->assertOk()->assertSee('data-theme-toggle', false)->getContent();

            $this->assertLessThan(strpos($html, '/build/assets/'), strpos($html, "localStorage.getItem('zutomarket-theme')"));
        }
    }

    public function test_feedback_is_stored_and_read_in_the_admin_inbox(): void
    {
        $this->get('/kritik-saran')->assertOk()->assertSee('action="'.route('feedback.store').'"', false)->assertSee('Laporan masalah')->assertSee('Kirim ke admin')->assertDontSee('mailto:', false);

        $this->post('/kritik-saran', ['type' => 'Saran', 'name' => 'Budi', 'contact' => '0812 3456 7890', 'message' => "Tambah game Valorant\ndan PUBG"])
            ->assertRedirect(route('feedback'))->assertSessionHas('success');
        $this->post('/kritik-saran', ['type' => 'Spam', 'message' => 'x'])->assertSessionHasErrors(['type', 'message']);
        $this->post('/kritik-saran', ['type' => 'Kritik', 'message' => 'Iklan murah sekali', 'website' => 'http://spam.example'])->assertRedirect(route('feedback'));
        $this->assertSame(1, FeedbackMessage::count());

        $buyer = User::factory()->buyer()->create(['name' => 'Sari', 'email' => 'sari@example.com']);
        $this->actingAs($buyer)->get('/kritik-saran')->assertSee('value="Sari"', false)->assertSee('value="sari@example.com"', false);
        $this->post('/kritik-saran', ['type' => 'Laporan masalah', 'name' => 'Sari', 'contact' => 'sari@example.com', 'message' => 'Gambar tidak muncul']);
        $this->assertSame($buyer->id, FeedbackMessage::latest('id')->first()->user_id);
        $this->get('/admin/messages')->assertForbidden();
        $this->post('/keluar');
        $this->get('/admin/messages')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertSee('Pesan masuk')->assertSee('2 pesan belum dibaca');
        $this->get('/admin/messages')->assertOk()->assertSeeInOrder(['akun sari@example.com', 'Gambar tidak muncul', 'Budi', 'Tambah game Valorant'])
            ->assertSee('https://wa.me/6281234567890', false)->assertSee('mailto:sari@example.com', false)->assertSee('2 pesan belum dibaca.');

        $first = FeedbackMessage::where('name', 'Budi')->first();
        $this->patch('/admin/messages/'.$first->id.'/read')->assertRedirect();
        $this->assertNotNull($first->fresh()->read_at);
        $this->get('/admin/messages')->assertSee('1 pesan belum dibaca.');
        $this->post('/admin/messages/read-all')->assertSessionHas('success');
        $this->get('/admin/messages')->assertSee('Semua pesan sudah dibaca.')->assertDontSee('pesan belum dibaca"', false);
        $this->delete('/admin/messages/'.$first->id)->assertSessionHas('success');
        $this->assertModelMissing($first);
    }

    public function test_detail_page_offers_stock_offer_and_buy_messages_and_same_category_recommendations(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        $category = Category::factory()->create(['name' => 'FF SULTAN']);
        $account = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'FF 5819', 'price' => 1999000, 'original_price' => 2800000]);
        $sameCategory = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'FF 5815']);
        GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'FF laku', 'status' => 'sold']);
        GameAccount::factory()->create(['title' => 'Akun lain']);

        $this->assertStringStartsWith('https://wa.me/6281234567890?text='.rawurlencode('Halo admin, saya ingin membeli akun FF 5819 seharga Rp 1.999.000.'), $account->buyWhatsappUrl());
        $this->assertStringEndsWith(rawurlencode('Penawaran saya: Rp ...'), $account->offerWhatsappUrl());

        $response = $this->get('/akun/'.$account->slug);

        $response->assertOk()->assertSee('Tanya Stok')->assertSee('>Tawar</a>', false)->assertSee('>Beli</a>', false)->assertSee('Hemat Rp 801.000 (-29%)')->assertSee('Beranda')->assertSee('Rekomendasi Produk');
        $this->assertSame(['FF 5815', 'Akun lain'], $response->viewData('recommendations')->pluck('title')->all());
        $response->assertDontSee('FF laku');
        $this->assertNotContains($account->id, $response->viewData('recommendations')->modelKeys());
        $this->assertSame($sameCategory->id, $response->viewData('recommendations')->first()->id);
    }

    public function test_header_search_and_home_shortcuts_point_to_the_catalog(): void
    {
        $category = Category::factory()->create(['name' => 'STOK ML', 'slug' => 'stok-ml']);
        GameAccount::factory()->count(2)->create(['category_id' => $category->id]);

        $this->get('/')->assertOk()->assertSee('id="header-search"', false)->assertSee('placeholder="Cari produk..."', false)
            ->assertSee('aria-label="Pintasan"', false)->assertSee(route('rental'), false)->assertSee(route('joki'), false)
            ->assertSee(route('categories.show', 'stok-ml').'#katalog', false)->assertSee('2 akun tersedia');
        $this->get('/katalog?q=mythic')->assertOk()->assertSee('id="header-search" type="search" name="q" value="mythic"', false);
        $this->get('/admin/login')->assertOk()->assertDontSee('id="header-search"', false);
    }

    public function test_admin_manages_info_posters_shown_on_the_home_page(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->get('/admin/infos')->assertRedirect('/admin/login');
        $this->get('/')->assertOk()->assertDontSee('Info Terbaru');
        $this->actingAs(User::factory()->create());

        $this->post('/admin/infos', ['title' => 'Cara beli akun', 'image' => UploadedFile::fake()->createWithContent('cara.png', $png), 'sort_order' => 2])->assertSessionHasNoErrors();
        $this->post('/admin/infos', ['title' => 'Cek akun FF', 'image' => UploadedFile::fake()->createWithContent('cek.png', $png), 'link_url' => 'https://zutomarket.id/joki-ml', 'sort_order' => 1])->assertSessionHasNoErrors();
        $first = InfoPost::where('title', 'Cara beli akun')->firstOrFail();
        Storage::disk('public')->assertExists($first->image_path);

        $this->get('/')->assertOk()->assertSee('Info Terbaru')->assertSeeInOrder(['aria-label="Cek akun FF"', 'aria-label="Cara beli akun"'], false)
            ->assertSee('href="https://zutomarket.id/joki-ml"', false)->assertSee('href="'.$first->imageUrl().'"', false);

        $this->post('/admin/infos', ['title' => 'Tanpa gambar'])->assertSessionHasErrors('image');
        $this->post('/admin/infos', ['title' => 'Salah', 'image' => UploadedFile::fake()->createWithContent('x.png', $png), 'link_url' => 'zutomarket.id'])->assertSessionHasErrors(['link_url' => 'Isi tautan lengkap yang diawali https://.']);

        $oldPath = $first->image_path;
        $this->put('/admin/infos/'.$first->id, ['title' => 'Cara beli akun baru', 'image' => UploadedFile::fake()->createWithContent('baru.png', $png)])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertSame('Cara beli akun baru', $first->fresh()->title);

        $this->delete('/admin/infos/'.$first->id)->assertRedirect();
        Storage::disk('public')->assertMissing($first->fresh()?->image_path ?? 'info/none');
        $this->assertDatabaseMissing('info_posts', ['id' => $first->id]);
    }

    public function test_removed_optional_features_are_not_publicly_reachable(): void
    {
        foreach (['/tukar-tambah', '/admin/finance', '/admin/trade-requests'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
