<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin > Pesanan website (super admin): orders paid through Tripay. After payment the admin hands the account
 * over (live chat / WhatsApp) and marks the order "Sudah diserahkan".
 */
class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = ['todo' => 'Perlu diserahkan', 'unpaid' => 'Menunggu pembayaran', 'done' => 'Selesai', 'other' => 'Kedaluwarsa / gagal'];
        $data = $request->validate(['tab' => ['nullable', Rule::in(array_keys($filters))], 'q' => 'nullable|string|max:60']);
        $tab = $data['tab'] ?? 'todo';
        $apply = fn ($query, string $tab) => match ($tab) {
            'todo' => $query->where('status', 'paid')->whereNull('delivered_at'),
            'unpaid' => $query->where('status', 'unpaid'),
            'done' => $query->where(fn ($q) => $q->where('status', 'paid')->whereNotNull('delivered_at')->orWhere('status', 'refunded')),
            'other' => $query->whereIn('status', ['expired', 'failed']),
        };
        $query = Order::with(['items', 'user:id,name,email']);
        if ($term = trim($data['q'] ?? '')) {
            $query->where(fn ($q) => $q->where('merchant_ref', 'like', "%{$term}%")->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', '%'.preg_replace('/\D+/', '', $term).'%')->orWhereHas('items', fn ($q) => $q->where('title', 'like', "%{$term}%")));
        }

        return view('admin.orders', [
            'tab' => $tab,
            'filters' => $filters,
            'query' => $data['q'] ?? '',
            'orders' => $apply($query, $tab)->latest('id')->paginate(15)->withQueryString(),
            'counts' => collect($filters)->map(fn ($label, string $key): int => $apply(Order::query(), $key)->count()),
        ]);
    }

    /**
     * "Chat pembeli": open (or start) the live chat with the order's buyer, with a hand-over message ready to edit.
     * The conversation only shows in Admin > Live chat once a message has been sent.
     */
    public function chat(Order $order): RedirectResponse
    {
        $conversation = ChatConversation::firstOrCreate(['user_id' => $order->user_id]);
        $titles = $order->items()->pluck('title')->implode(', ');
        $draft = $order->status === 'paid'
            ? 'Halo '.$order->customer_name.', terima kasih sudah membayar pesanan '.$order->merchant_ref.' ('.$titles.'). Berikut data akunnya:'
            : 'Halo '.$order->customer_name.', ini admin ZutoMarket soal pesanan '.$order->merchant_ref.' ('.$titles.').';

        return redirect()->route('admin.chat.show', $conversation)->with('chat_draft', $draft);
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->status === 'paid', 422);
        $delivered = $request->boolean('delivered');
        $order->forceFill(['delivered_at' => $delivered ? now() : null])->save();

        return back()->with('success', $delivered ? 'Pesanan '.$order->merchant_ref.' ditandai sudah diserahkan.' : 'Tanda serah terima dibatalkan.');
    }
}
