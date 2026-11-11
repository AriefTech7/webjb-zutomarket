<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Masuk dengan Google" for buyers. Only active when GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET are set.
 */
class GoogleAuthController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): SymfonyRedirect
    {
        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);
        $failed = fn (string $message) => redirect()->route('account.login')->withErrors(['email' => $message]);
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            Log::warning('Google login failed', ['error' => $exception->getMessage()]);

            return $failed('Login Google dibatalkan atau gagal. Coba lagi.');
        }
        $email = Str::lower((string) $google->getEmail());
        if ($email === '' || ($google->getRaw()['email_verified'] ?? false) !== true) {
            return $failed('Email akun Google belum terverifikasi.');
        }

        $user = User::where('google_id', $google->getId())->first() ?? User::where('email', $email)->first();
        if ($user?->is_admin) {
            return $failed('Email ini dipakai akun admin. Admin masuk lewat halaman login admin.');
        }
        if (! $user) {
            $user = User::create(['name' => Str::limit($google->getName() ?: Str::before($email, '@'), 50, ''), 'email' => $email, 'password' => Str::password(40)]);
        }
        $user->forceFill(['google_id' => $google->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('profile'))->with('success', 'Berhasil masuk dengan Google.')->with('wishlist_merge', true);
    }
}
