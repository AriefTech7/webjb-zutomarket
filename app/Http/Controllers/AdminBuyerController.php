<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Buyer accounts in the admin. While the server cannot send email, a forgotten password is handled here:
 * the buyer asks on WhatsApp and the admin issues a one-time temporary password.
 */
class AdminBuyerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) ($request->validate(['q' => 'nullable|string|max:100'])['q'] ?? ''));
        $buyers = User::where('is_admin', false)
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")->orWhere('whatsapp', 'like', "%{$search}%")))
            ->latest()->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.buyers', ['buyers' => $buyers, 'search' => $search]);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403, 'Password admin tidak bisa direset dari sini.');
        // Unambiguous characters only, so it can be read out or typed from a WhatsApp message.
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $temporary = '';
        for ($i = 0; $i < 10; $i++) {
            $temporary .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $user->forceFill(['password' => $temporary, 'remember_token' => Str::random(60)])->save();

        return back()->with('success', 'Password sementara dibuat untuk '.$user->email.'.')
            ->with('temporary_password', ['email' => $user->email, 'password' => $temporary, 'whatsapp' => $user->whatsapp]);
    }
}
