<?php

namespace App\Http\Controllers;

use App\Models\FeedbackMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kritik & saran: the public form stores the message; admins read it in Admin > Pesan masuk.
 */
class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('catalog.feedback');
    }

    public function store(Request $request): RedirectResponse
    {
        // Hidden "website" field: people never fill it, simple spam bots do. Pretend it worked.
        if (filled($request->input('website'))) {
            return redirect()->route('feedback')->with('success', 'Terima kasih! Pesanmu sudah terkirim ke admin.');
        }
        $data = $request->validate([
            'type' => ['required', Rule::in(FeedbackMessage::TYPES)],
            'name' => ['nullable', 'string', 'max:50'],
            'contact' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:5', 'max:1500'],
        ], ['message.min' => 'Pesan minimal 5 karakter.']);
        $user = $request->user();
        FeedbackMessage::create([...$data, 'user_id' => $user?->is_admin ? null : $user?->id]);

        return redirect()->route('feedback')->with('success', 'Terima kasih! Pesanmu sudah terkirim ke admin.');
    }

    public function index(): View
    {
        return view('admin.messages', [
            'messages' => FeedbackMessage::with('user:id,email')->latest()->orderByDesc('id')->paginate(20),
            'unread' => FeedbackMessage::whereNull('read_at')->count(),
        ]);
    }

    public function markRead(FeedbackMessage $message): RedirectResponse
    {
        $message->forceFill(['read_at' => $message->read_at ? null : now()])->save();

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        FeedbackMessage::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Semua pesan ditandai sudah dibaca.');
    }

    public function destroy(FeedbackMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Pesan dihapus.');
    }
}
