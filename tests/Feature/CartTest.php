<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_cards_have_share_like_and_cart_buttons(): void
    {
        $available = GameAccount::factory()->create(['title' => 'Akun Siap']);
        GameAccount::factory()->create(['title' => 'Akun Laku', 'status' => 'sold']);

        $this->get('/katalog')->assertOk()
            ->assertSee('data-share-url="'.route('accounts.show', $available->slug).'"', false)->assertSee('Share')
            ->assertSee('data-wishlist-toggle="'.$available->id.'"', false)->assertSee('Suka')
            ->assertSee('data-cart-toggle="'.$available->id.'"', false)->assertSee('Keranjang')
            ->assertSee(route('cart'), false);
        $this->get('/katalog?status=sold')->assertOk()->assertSee('Akun Laku')->assertDontSee('data-cart-toggle=', false);
        $this->get('/akun/'.$available->slug)->assertOk()->assertSee('Tambah ke keranjang');
    }

    public function test_cart_page_lists_only_buyable_accounts_with_one_whatsapp_checkout_per_seller(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281111111111']);
        $category = Category::factory()->create(['name' => 'Stok ML']);
        $first = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'Akun A', 'price' => 100000]);
        $second = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'Akun B', 'price' => 250000]);
        $seller = GameAccount::factory()->create(['category_id' => $category->id, 'title' => 'Akun Penjual', 'price' => 50000, 'seller_whatsapp' => '6282222222222']);
        $booked = GameAccount::factory()->create(['status' => 'dp']);
        $sold = GameAccount::factory()->create(['status' => 'sold']);
        $rental = GameAccount::factory()->create(['is_rental' => true]);

        $this->get('/keranjang')->assertOk()->assertSee('data-cart-page', false)->assertSee('Keranjang masih kosong');

        $response = $this->getJson('/keranjang/accounts?'.http_build_query(['ids' => [$second->id, $booked->id, $first->id, $sold->id, $seller->id, $rental->id]]))->assertOk();
        $this->assertSame([$second->id, $first->id, $seller->id], $response->json('ids'));
        $html = $response->json('html');
        $this->assertStringContainsString('Rp 400.000', $html);
        $this->assertStringContainsString('ditangani 2 penjual berbeda', $html);
        $storeMessage = "Halo admin, saya ingin membeli akun berikut dari keranjang:\n\n1. Akun B (Stok ML) - Rp 250.000\n   ".route('accounts.show', $second->slug)
            ."\n2. Akun A (Stok ML) - Rp 100.000\n   ".route('accounts.show', $first->slug)."\n\nTotal: Rp 350.000 (2 akun). Apakah masih tersedia?";
        $this->assertStringContainsString(e('https://wa.me/6281111111111?text='.rawurlencode($storeMessage)), $html);
        $this->assertStringContainsString('https://wa.me/6282222222222?text=', $html);
        $this->assertStringNotContainsString($booked->title, $html);

        $this->getJson('/keranjang/accounts?'.http_build_query(['ids' => range(1, 51)]))->assertUnprocessable();
        $this->getJson('/keranjang/accounts?ids[]=abc')->assertUnprocessable();
        $this->getJson('/keranjang/accounts')->assertOk()->assertJson(['ids' => []]);
    }
}
