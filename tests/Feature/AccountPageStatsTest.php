<?php

namespace Tests\Feature;

use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The account page's "Dilihat" / "Suka" counters, flash sale timer and "Admin penjual" box. */
class AccountPageStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visit_counts_once_per_session_and_admins_are_not_counted(): void
    {
        $account = GameAccount::factory()->create(['view_count' => 3099]);
        $updatedAt = $account->updated_at;

        $this->get('/akun/'.$account->slug)->assertOk()->assertSee('Dilihat')->assertSee('3,1 rb');
        $this->get('/akun/'.$account->slug)->assertOk();
        $this->assertSame(3100, $account->fresh()->view_count);
        $this->assertEquals($updatedAt, $account->fresh()->updated_at);

        $this->actingAs(User::factory()->create())->withSession([])->get('/akun/'.$account->slug)->assertOk();
        $this->assertSame(3100, $account->fresh()->view_count);
    }

    public function test_likes_follow_the_wishlist_button_once_per_session(): void
    {
        $account = GameAccount::factory()->create();

        $this->postJson('/suka/'.$account->id, ['liked' => true])->assertOk()->assertJson(['likes' => 1, 'label' => '1']);
        $this->postJson('/suka/'.$account->id, ['liked' => true])->assertJson(['likes' => 1]);
        $this->postJson('/suka/'.$account->id, ['liked' => false])->assertJson(['likes' => 0]);
        // Never below zero, and an unlike from a session that never liked changes nothing.
        $this->postJson('/suka/'.$account->id, ['liked' => false])->assertJson(['likes' => 0]);
        $this->postJson('/suka/'.$account->id, [])->assertUnprocessable();

        $account->update(['status' => 'draft']);
        $this->postJson('/suka/'.$account->id, ['liked' => true])->assertNotFound();
    }

    public function test_compact_counts(): void
    {
        $this->assertSame(['0', '950', '1 rb', '3,1 rb', '12,5 rb', '1,3 jt'], array_map(GameAccount::compactCount(...), [0, 950, 1000, 3100, 12500, 1250000]));
    }

    public function test_the_panel_shows_the_flash_sale_timer_and_the_selling_admin(): void
    {
        $admin = User::factory()->create(['name' => 'Razan']);
        $account = GameAccount::factory()->create(['owner_id' => $admin->id, 'flash_sale_ends_at' => now()->addHours(2)->addMinutes(5), 'original_price' => 500000, 'price' => 350000]);

        $this->get('/akun/'.$account->slug)->assertOk()
            ->assertSee('data-countdown-parts=', false)->assertSee('Penawaran terbatas')->assertSee('>02</span>', false)
            ->assertSee('Admin penjual')->assertSee('Razan')
            ->assertSee('data-like-count="'.$account->id.'"', false)
            ->assertSee('data-share-url=', false);

        $account->update(['status' => 'sold']);
        $this->get('/akun/'.$account->slug)->assertOk()->assertDontSee('data-countdown-parts=', false)->assertSee('Terjual');
    }
}
