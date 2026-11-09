<?php

namespace Tests\Feature;

use App\Models\GameAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_wishlist_and_see_browser_storage_explanation(): void
    {
        $this->get('/wishlist')->assertOk()->assertSee('Wishlist saya')->assertSee('tanpa login')
            ->assertSee('tidak tersinkron ke perangkat lain')->assertSee('data-wishlist-page', false);
        $this->assertGuest();
    }

    public function test_catalog_and_detail_have_wishlist_buttons_and_navigation(): void
    {
        $account = GameAccount::factory()->create();

        $this->get('/')->assertOk()->assertSee('data-wishlist-toggle="'.$account->id.'"', false)
            ->assertSee(route('wishlist'), false)->assertSee('data-wishlist-count', false);
        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Simpan ke wishlist')
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_wishlist_returns_current_public_accounts_in_requested_order(): void
    {
        $available = GameAccount::factory()->create(['title' => 'Akun diperbarui', 'price' => 200000, 'cost_price' => 123456]);
        $sold = GameAccount::factory()->create(['title' => 'Akun terjual', 'status' => 'sold']);
        GameAccount::factory()->create(['title' => 'Bukan wishlist']);
        $available->update(['price' => 250000]);

        $response = $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$sold->id, $available->id]]));

        $response->assertOk()->assertJsonPath('ids', [$sold->id, $available->id]);
        $html = $response->json('html');
        $this->assertStringContainsString('Rp 250.000', $html);
        $this->assertStringContainsString('Terjual', $html);
        $this->assertStringNotContainsString('Bukan wishlist', $html);
        $this->assertStringNotContainsString('123456', $html);
        $this->assertLessThan(strpos($html, 'Akun diperbarui'), strpos($html, 'Akun terjual'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_draft_deleted_and_missing_accounts_are_excluded_for_browser_cleanup(): void
    {
        $public = GameAccount::factory()->create();
        $draft = GameAccount::factory()->create(['title' => 'Rahasia draft', 'status' => 'draft']);
        $deleted = GameAccount::factory()->create(['title' => 'Sudah dihapus']);
        $deleted->delete();

        $response = $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$public->id, $draft->id, $deleted->id, 999999]]));

        $response->assertOk()->assertJsonPath('ids', [$public->id]);
        $this->assertStringNotContainsString('Rahasia draft', $response->json('html'));
        $this->assertStringNotContainsString('Sudah dihapus', $response->json('html'));
        $this->assertDatabaseCount('game_accounts', 3);
    }

    public function test_no_ids_returns_empty_wishlist_instead_of_entire_catalog(): void
    {
        GameAccount::factory()->create();

        $this->getJson('/wishlist/accounts')->assertOk()->assertJsonPath('ids', [])->assertJsonPath('html', '');
    }

    public function test_saved_account_uses_latest_status_and_disappears_when_unpublished(): void
    {
        $account = GameAccount::factory()->create();
        $url = '/wishlist/accounts?'.http_build_query(['ids' => [$account->id]]);
        $this->getJson($url)->assertJsonPath('ids', [$account->id]);
        $account->update(['status' => 'sold']);

        $sold = $this->getJson($url);

        $sold->assertOk()->assertJsonPath('ids', [$account->id]);
        $this->assertStringContainsString('Terjual', $sold->json('html'));
        $account->update(['status' => 'draft']);
        $this->getJson($url)->assertOk()->assertJsonPath('ids', [])->assertJsonPath('html', '');
    }

    public function test_wishlist_card_escapes_account_text_and_keeps_cost_private(): void
    {
        $account = GameAccount::factory()->create(['title' => '<script>alert(1)</script>', 'cost_price' => 87654321]);

        $response = $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => [$account->id]]));

        $response->assertOk();
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->json('html'));
        $this->assertStringNotContainsString('87654321', $response->json('html'));
    }

    #[TestWith(['invalid', 'ids'])]
    #[TestWith([['invalid'], 'ids.0'])]
    #[TestWith([[0], 'ids.0'])]
    #[TestWith([[-1], 'ids.0'])]
    #[TestWith([[1.5], 'ids.0'])]
    #[TestWith([[9007199254740992], 'ids.0'])]
    #[TestWith([[1, 1], 'ids.0'])]
    public function test_invalid_ids_are_rejected(string|array $ids, string $field): void
    {
        $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => $ids]))
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public function test_wishlist_lookup_is_limited_to_one_hundred_accounts(): void
    {
        $this->getJson('/wishlist/accounts?'.http_build_query(['ids' => range(1, 101)]))
            ->assertUnprocessable()->assertJsonValidationErrors('ids');
    }
}
