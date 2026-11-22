<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Tripay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * The buyer's orders (/pesanan) and the Tripay callback. Tripay's callback is the source of truth; the order page
 * also asks Tripay now and then, so a missed callback does not leave a paid order looking unpaid.
 */
class OrderController extends Controller
{
    public function __construct(private readonly Tripay $tripay) {}

    public function index(Request $request): View
    {
        return view('checkout.orders', ['orders' => $request->user()->orders()->with('items')->latest('id')->paginate(10)]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $this->refresh($order);

        return view('checkout.show', ['order' => $order->load('items.account')]);
    }

    /** Polled by the order page while it waits for payment. */
    public function status(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $this->refresh($order);

        return response()->json(['status' => $order->status])->header('Cache-Control', 'no-store');
    }

    public function callback(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        if (! Tripay::validCallbackSignature($raw, $request->header('X-Callback-Signature'))) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }
        if ($request->header('X-Callback-Event') !== 'payment_status') {
            return response()->json(['success' => false, 'message' => 'Unknown event'], 400);
        }
        $data = json_decode($raw, true) ?: [];
        $order = Order::where('merchant_ref', (string) ($data['merchant_ref'] ?? ''))->first();
        if (! $order || ($order->tripay_reference && $order->tripay_reference !== ($data['reference'] ?? null))) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        $order->applyTripayStatus((string) ($data['status'] ?? ''));

        return response()->json(['success' => true]);
    }

    /** Ask Tripay at most every 15 seconds while unpaid; expire it locally once the window has passed. */
    private function refresh(Order $order): void
    {
        if ($order->status !== 'unpaid') {
            return;
        }
        if ($order->tripay_reference && (! $order->checked_at || $order->checked_at->lt(now()->subSeconds(15)))) {
            $order->forceFill(['checked_at' => now()])->save();
            try {
                if ($status = $this->tripay->status($order->tripay_reference)) {
                    $order->applyTripayStatus($status);
                }
            } catch (Throwable $exception) {
                Log::info('Tripay status check failed', ['ref' => $order->merchant_ref, 'error' => $exception->getMessage()]);
            }
        }
        if ($order->status === 'unpaid' && $order->expires_at?->isPast()) {
            $order->update(['status' => 'expired']);
        }
    }
}
