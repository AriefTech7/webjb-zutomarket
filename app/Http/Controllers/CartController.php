<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The cart lives in the buyer's browser (like the guest wishlist). The server only turns the saved ids into
 * the accounts that can still be bought and one WhatsApp checkout message per seller.
 */
class CartController extends Controller
{
    public const LIMIT = 50;

    public function index(): View
    {
        return view('catalog.cart');
    }

    public function accounts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['sometimes', 'array', 'max:'.self::LIMIT],
            'ids.*' => ['required', 'integer', 'min:1', 'max:9007199254740991', 'distinct'],
        ]);
        $ids = array_map(intval(...), $data['ids'] ?? []);
        $positions = array_flip($ids);
        $accounts = GameAccount::with(['category:id,name', 'owner'])->whereIn('id', $ids)->forSale()->where('status', 'available')
            ->get()->sortBy(fn (GameAccount $account): int => $positions[$account->id])->values();

        return response()->json([
            'ids' => $accounts->modelKeys(),
            'html' => view('catalog.cart-items', [
                'accounts' => $accounts,
                // The super admin's accounts are paid together on the website; the others go to WhatsApp per seller.
                'gateway' => $gateway = $accounts->filter(fn (GameAccount $account): bool => $account->usesPaymentGateway())->values(),
                'checkouts' => $this->checkouts($accounts->diff($gateway)->values()),
            ])->render(),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * One WhatsApp order per contact number, since most accounts are handled by their own seller.
     *
     * @param  Collection<int, GameAccount>  $accounts
     * @return Collection<int, array{count: int, total: int, url: ?string}>
     */
    private function checkouts(Collection $accounts): Collection
    {
        $rupiah = fn (int $amount): string => 'Rp '.number_format($amount, 0, ',', '.');

        return $accounts->groupBy(fn (GameAccount $account): string => $account->contactNumber())->map(function (Collection $group, string $number) use ($rupiah): array {
            $total = (int) $group->sum('price');
            $lines = $group->values()->map(fn (GameAccount $account, int $index): string => ($index + 1).'. '.$account->title.' ('.$account->category->name.') - '.$account->formattedPrice()."\n   ".route('accounts.show', $account->slug));
            $message = "Halo admin, saya ingin membeli akun berikut dari keranjang:\n\n".$lines->implode("\n")."\n\nTotal: ".$rupiah($total).' ('.$group->count().' akun). Apakah masih tersedia?';

            return ['count' => $group->count(), 'total' => $total, 'url' => $number ? 'https://wa.me/'.$number.'?text='.rawurlencode($message) : null];
        })->values();
    }
}
