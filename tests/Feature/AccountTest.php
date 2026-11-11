<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_register_and_is_never_an_admin(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $this->get('/daftar')->assertOk()->assertSee('Daftar Akun Baru')->assertSee('Kebijakan Privasi')->assertDontSee('Daftar dengan Google');

        $this->post('/daftar', ['name' => 'Budi', 'email' => 'Budi@Example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
            ->assertRedirect(route('profile'))->assertSessionHas('wishlist_merge', true);

        $buyer = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertFalse($buyer->is_admin);
        $this->assertNull($buyer->username);
        $this->assertAuthenticatedAs($buyer);
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/accounts')->assertForbidden();
        $this->post('/admin/categories', ['name' => 'Hack'])->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Hack']);
    }

    public function test_registration_validates_input_and_refuses_duplicate_email(): void
    {
        User::factory()->buyer()->create(['email' => 'ada@example.com']);

        $this->post('/daftar', ['name' => 'X', 'email' => 'ADA@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])->assertSessionHasErrors('email');
        $this->post('/daftar', ['name' => 'X', 'email' => 'baru@example.com', 'password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
        $this->post('/daftar', ['name' => 'X', 'email' => 'baru@example.com', 'password' => 'rahasia123', 'password_confirmation' => 'lain12345'])->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_buyer_logs_in_with_email_and_admin_logins_stay_separate(): void
    {
        $buyer = User::factory()->buyer()->create(['email' => 'pembeli@example.com', 'password' => 'rahasia123']);
        $admin = User::factory()->create(['username' => 'bos', 'email' => 'bos@example.com', 'password' => 'rahasia123']);

        $this->get('/masuk')->assertOk()->assertSee('Masuk ke Akunmu')->assertSee('Lupa password?');
        // Admin credentials do not work on the buyer form, and buyer credentials do not open the admin login.
        $this->post('/masuk', ['email' => 'bos@example.com', 'password' => 'rahasia123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['username' => (string) $buyer->email, 'password' => 'rahasia123'])->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post('/masuk', ['email' => 'Pembeli@example.com', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/masuk', ['email' => 'Pembeli@example.com', 'password' => 'rahasia123'])->assertRedirect(route('profile'));
        $this->assertAuthenticatedAs($buyer);
        $this->get('/masuk')->assertRedirect(route('profile'));
        $this->post('/keluar')->assertRedirect(route('catalog'));
        $this->assertGuest();

        $this->post('/admin/login', ['username' => 'bos', 'password' => 'rahasia123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_existing_users_become_admins_when_the_migration_runs(): void
    {
        $this->assertTrue(User::factory()->create()->fresh()->is_admin);
        $this->assertFalse(User::factory()->buyer()->create()->fresh()->is_admin);
        $this->assertFalse((new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'x', 'is_admin' => true]))->is_admin ?? false);
    }

    public function test_buyer_profile_and_wishlist_are_saved_on_the_account(): void
    {
        $buyer = User::factory()->buyer()->create(['name' => 'Sari', 'wishlist' => [5, 9]]);
        $this->get('/profil')->assertOk()->assertSee('Masuk')->assertSee('Daftar')->assertDontSee('data-profile-form', false)->assertSee('Wishlist saya');

        $this->actingAs($buyer);
        $this->get('/profil')->assertOk()->assertSee('Halo, Sari')->assertSee('Keluar dari akun')->assertDontSee('data-profile-form', false)
            ->assertSee('data-account-wishlist-ids="[5,9]"', false)->assertSee('data-account-profile=', false);
        $this->put('/akun/profil', ['name' => 'Sari Dewi', 'whatsapp' => '0812 3456 7890'])->assertRedirect();
        $this->assertSame('0812 3456 7890', $buyer->fresh()->whatsapp);
        $this->put('/akun/profil', ['name' => 'Sari', 'whatsapp' => 'bukan nomor'])->assertSessionHasErrors('whatsapp');

        $this->postJson('/akun/wishlist', ['ids' => [7, 5], 'merge' => true])->assertOk()->assertExactJson(['ids' => [7, 5, 9]]);
        $this->postJson('/akun/wishlist', ['ids' => [9]])->assertOk()->assertExactJson(['ids' => [9]]);
        $this->assertSame([9], $buyer->fresh()->wishlistIds());
        $this->postJson('/akun/wishlist', ['ids' => ['x']])->assertUnprocessable();
    }

    public function test_guests_cannot_use_account_endpoints(): void
    {
        $this->postJson('/akun/wishlist', ['ids' => [1]])->assertUnauthorized();
        $this->put('/akun/profil', ['name' => 'x'])->assertRedirect(route('account.login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_forgot_password_falls_back_to_whatsapp_until_mail_is_configured(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        config(['mail.default' => 'log']);
        $this->get('/lupa-password')->assertOk()->assertSee('belum aktif')->assertSee('https://wa.me/6281234567890', false);
        $this->post('/lupa-password', ['email' => 'a@example.com'])->assertNotFound();

        config(['mail.default' => 'smtp']);
        Notification::fake();
        $buyer = User::factory()->buyer()->create(['email' => 'lupa@example.com']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->get('/lupa-password')->assertOk()->assertSee('Kirim tautan reset');
        $this->post('/lupa-password', ['email' => 'lupa@example.com'])->assertSessionHas('success');
        $this->post('/lupa-password', ['email' => 'admin@example.com'])->assertSessionHas('success');
        Notification::assertNotSentTo($admin, ResetPassword::class);
        Notification::assertSentTo($buyer, ResetPassword::class, function (ResetPassword $notification) use ($buyer): bool {
            $this->get('/reset-password/'.$notification->token.'?email=lupa@example.com')->assertOk()->assertSee('Buat Password Baru');
            $this->post('/reset-password', ['token' => $notification->token, 'email' => 'lupa@example.com', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
                ->assertRedirect(route('account.login'));
            $this->post('/masuk', ['email' => 'lupa@example.com', 'password' => 'baru12345']);
            $this->assertAuthenticatedAs($buyer);

            return true;
        });
    }

    public function test_google_login_is_hidden_until_configured_and_never_signs_into_an_admin(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $this->get('/auth/google')->assertNotFound();
        $this->get('/masuk')->assertDontSee('Masuk dengan Google');

        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $this->get('/masuk')->assertSee('Masuk dengan Google');
        $this->get('/daftar')->assertSee('Daftar dengan Google');

        User::factory()->create(['email' => 'admin@example.com']);
        $google = fn (string $email, bool $verified = true) => Socialite::shouldReceive('driver->user')->once()->andReturn(
            (new SocialiteUser)->map(['id' => 'g-'.$email, 'name' => 'Nama Google', 'email' => $email])->setRaw(['email_verified' => $verified]));

        $google('admin@example.com');
        $this->get('/auth/google/callback')->assertRedirect(route('account.login'));
        $this->assertGuest();

        $google('belum@example.com', false);
        $this->get('/auth/google/callback')->assertRedirect(route('account.login'));
        $this->assertGuest();

        $google('Baru@Example.com');
        $this->get('/auth/google/callback')->assertRedirect(route('profile'));
        $user = User::where('email', 'baru@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertSame('g-Baru@Example.com', $user->google_id);
    }

    public function test_footer_lists_games_menu_and_privacy_page(): void
    {
        Setting::create(['key' => 'whatsapp_number', 'value' => '6281234567890']);
        Category::factory()->create(['name' => 'Free Fire', 'slug' => 'free-fire']);
        GameAccount::factory()->count(0)->create();

        $this->get('/')->assertOk()->assertSee('Game Tersedia')->assertSee(route('categories.show', 'free-fire'), false)
            ->assertSee('WhatsApp Admin')->assertSee('Sinyal Trust')->assertSee('Created By')->assertSee('OzinVoid')->assertSee(route('privacy'), false);
        $this->get('/kebijakan-privasi')->assertOk()->assertSee('Data yang kami simpan');
    }
}
