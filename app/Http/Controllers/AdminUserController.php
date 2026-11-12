<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Admin accounts. Creating or removing an admin asks for the signed-in admin's own password,
 * because an admin can see and change everything in the store.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.admins', ['admins' => User::where('is_admin', true)->orderBy('username')->get()]);
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
        $user->delete();

        return back()->with('success', 'Akun admin "'.$user->username.'" dihapus.');
    }
}
