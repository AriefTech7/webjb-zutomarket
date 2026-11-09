<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(): View
    {
        return view('catalog.wishlist');
    }

    public function accounts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['sometimes', 'array', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1', 'max:9007199254740991', 'distinct'],
        ]);
        $ids = array_map(intval(...), $data['ids'] ?? []);
        $positions = array_flip($ids);
        $accounts = GameAccount::query()->select(['id', 'category_id', 'title', 'slug', 'price', 'main_image', 'status', 'specifications'])
            ->with('category:id,name')->whereIn('id', $ids)->whereIn('status', ['available', 'sold'])
            ->get()->sortBy(fn (GameAccount $account): int => $positions[$account->id])->values();

        return response()->json([
            'ids' => $accounts->modelKeys(),
            'html' => view('catalog.wishlist-accounts', compact('accounts'))->render(),
        ])->header('Cache-Control', 'no-store');
    }
}
