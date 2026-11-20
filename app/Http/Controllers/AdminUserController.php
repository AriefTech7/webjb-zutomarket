<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Admin accounts (super admin only). Creating, promoting or removing an admin asks for the signed-in admin's own
 * password. Regular admins only manage the game accounts they own; super admins manage the whole store.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.admins', ['admins' => User::where('is_admin', true)->withCount('ownedAccounts')->orderByDesc('is_super_admin')->orderBy('username')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['username' => Str::lower(trim((string) $request->input('username'))), 'email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'current_password' => ['required', 'current_password'],
        ], [
            'username.unique' => 'Username ini sudah dipakai.',
            'email.unique' => 'Email ini sudah dipakai akun lain (admin atau pembeli).',
            'current_password.current_password' => 'Password kamu salah.',
            'current_password.required' => 'Isi password kamu untuk konfirmasi.',
        ]);
        $admin = User::create(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'], 'password' => $data['password']]);
        $admin->forceFill(['is_admin' => true])->save();

        return redirect()->route('admin.admins')->with('success', 'Akun admin "'.$admin->username.'" berhasil dibuat. Login di /admin/login.');
    }

    /** Change an admin's name, username or email. Confirmed with the super admin's own password. */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $request->merge(['username' => Str::lower(trim((string) $request->input('username'))), 'email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validateWithBag('admin'.$user->id, [
            'name' => ['required', 'string', 'max:50'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], self::MESSAGES);
        $user->forceFill(['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email']])->save();

        return back()->with('success', 'Data admin "'.$user->username.'" diperbarui.');
    }

    /**
     * Set a new password for another admin (e.g. they forgot it). Their other sessions are signed out because the
     * remember token changes. Admins change their own password at Admin > Ganti password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $data = $request->validateWithBag('admin'.$user->id, [
            'new_password' => ['required', 'confirmed', Password::min(8)],
            'current_password' => ['required', 'current_password'],
        ], self::MESSAGES + ['new_password.confirmed' => 'Ulangi password baru dengan benar.']);
        if ($user->is($request->user())) {
            return back()->withErrors(['current_password' => 'Ganti password-mu sendiri lewat menu Ganti password.'], 'admin'.$user->id);
        }
        $user->forceFill(['password' => $data['new_password'], 'remember_token' => Str::random(60)])->save();

        return back()->with('success', 'Password admin "'.$user->username.'" diganti. Beri tahu password barunya secara pribadi.');
    }

    /** Every admin (regular or super) changes their own password here. */
    public function showOwnPassword(): View
    {
        return view('admin.password');
    }

    public function updateOwnPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], self::MESSAGES + ['password.confirmed' => 'Ulangi password baru dengan benar.', 'password.different' => 'Password baru harus berbeda dari password lama.']);
        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.password')->with('success', 'Password berhasil diganti.');
    }

    private const MESSAGES = [
        'username.unique' => 'Username ini sudah dipakai.',
        'email.unique' => 'Email ini sudah dipakai akun lain (admin atau pembeli).',
        'current_password.current_password' => 'Password kamu salah.',
        'current_password.required' => 'Isi password kamu untuk konfirmasi.',
    ];

    /** Promote an admin to super admin or back. Nobody changes their own role, so a super admin always remains. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.current_password' => 'Password kamu salah.',
            'current_password.required' => 'Isi password kamu untuk konfirmasi.',
        ]);
        if ($user->is($request->user())) {
            return back()->withErrors(['current_password' => 'Kamu tidak bisa mengubah peranmu sendiri.']);
        }
        $user->forceFill(['is_super_admin' => ! $user->is_super_admin])->save();

        return back()->with('success', '"'.$user->username.'" sekarang '.($user->is_super_admin ? 'super admin.' : 'admin biasa.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.current_password' => 'Password kamu salah.',
            'current_password.required' => 'Isi password kamu untuk konfirmasi.',
        ]);
        if ($user->is($request->user())) {
            return back()->withErrors(['current_password' => 'Kamu tidak bisa menghapus akunmu sendiri.']);
        }
        // Their game accounts stay in the store and move to the super admin who removed them.
        DB::transaction(function () use ($request, $user): void {
            GameAccount::withTrashed()->where('owner_id', $user->id)->update(['owner_id' => $request->user()->id]);
            $user->delete();
        });

        return back()->with('success', 'Akun admin "'.$user->username.'" dihapus.');
    }
}
