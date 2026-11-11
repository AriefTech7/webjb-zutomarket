<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Storefront buyer accounts. Buyers never get admin access: that stays behind is_admin and /admin/login.
 */
class AccountController extends Controller
{
    public static function mailReady(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    public function showLogin(): View
    {
        return view('auth.account-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate(['email' => ['required', 'email', 'max:255'], 'password' => ['required', 'string']]);
        if (! Auth::attempt([...$credentials, 'is_admin' => false], remember: true)) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('profile'))->with('success', 'Berhasil masuk. Selamat datang kembali!')->with('wishlist_merge', true);
    }

    public function showRegister(): View
    {
        return view('auth.account-register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], ['email.unique' => 'Email ini sudah terdaftar. Silakan masuk.']);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('profile')->with('success', 'Akun berhasil dibuat. Wishlist dan profilmu sekarang tersimpan di akun.')->with('wishlist_merge', true);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalog')->with('success', 'Kamu sudah keluar dari akun.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 \-]{8,20}$/'],
        ], ['whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda + atau -.']);
        $request->user()->update($data);

        return back()->with('success', 'Profil akun tersimpan.');
    }

    public function settings(Request $request): View
    {
        abort_if($request->user()->is_admin, 403, 'Admin mengatur akunnya lewat dashboard.');

        return view('account.settings');
    }

    public function showPassword(Request $request): View
    {
        abort_if($request->user()->is_admin, 403, 'Admin mengatur akunnya lewat dashboard.');

        return view('account.password');
    }

    /**
     * Google sign-ups got a random password they never saw, so for them the current password is optional.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user->is_admin, 403);
        $data = $request->validate([
            'current_password' => [$user->google_id ? 'nullable' : 'required', 'string', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], ['current_password.current_password' => 'Password saat ini salah.']);
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('profile')->with('success', 'Password berhasil diganti.');
    }

    /**
     * Keeps the browser wishlist and the account wishlist in step. With merge=1 (first page load after
     * signing in) the browser's ids are added to the saved ones; otherwise the browser's list replaces them.
     */
    public function syncWishlist(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['present', 'array', 'max:100'],
            'ids.*' => ['integer', 'min:1', 'max:9007199254740991'],
            'merge' => ['sometimes', 'boolean'],
        ]);
        $ids = array_map(intval(...), $data['ids']);
        $user = $request->user();
        if ($request->boolean('merge')) {
            $ids = [...$ids, ...$user->wishlistIds()];
        }
        $ids = array_slice(array_values(array_unique($ids)), 0, 100);
        $user->update(['wishlist' => $ids]);

        return response()->json(['ids' => $ids])->header('Cache-Control', 'no-store');
    }

    public function showForgot(): View
    {
        return view('auth.forgot-password', ['mailReady' => self::mailReady()]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        abort_unless(self::mailReady(), 404);
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        Password::sendResetLink(['email' => Str::lower($data['email']), 'is_admin' => false]);

        // Same answer whether or not the email exists, so the form cannot be used to look up accounts.
        return back()->with('success', 'Jika email tersebut terdaftar, tautan untuk membuat password baru sudah dikirim. Cek juga folder spam.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);
        $status = Password::reset([...$data, 'email' => Str::lower($data['email']), 'is_admin' => false], function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'Tautan reset sudah tidak berlaku. Minta tautan baru.']);
        }

        return redirect()->route('account.login')->with('success', 'Password berhasil diganti. Silakan masuk.');
    }
}
