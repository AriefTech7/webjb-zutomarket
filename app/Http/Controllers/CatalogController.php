<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\InfoPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, ?Category $category = null): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:255', 'sort' => 'nullable|in:newest,price_low,price_high', 'available' => 'nullable|in:1', 'status' => 'nullable|in:available,sold']);
        $status = $data['status'] ?? ($request->boolean('available') ? 'available' : null);
        $selected = $category?->slug ?? ($data['category'] ?? '');
        $query = GameAccount::with(['category', 'rentalPackages']);
        if ($selected) {
            // A category page shows everything in that category, rentals included (e.g. "Jasa Rental Akun").
            $query->whereHas('category', fn ($q) => $q->where('slug', $selected))
                ->where(fn ($q) => $q->where(fn ($q) => $q->forSale()->whereIn('status', GameAccount::PUBLIC_SALE_STATUSES))->orWhere(fn ($q) => $q->rental()));
        } else {
            $query->forSale()->whereIn('status', GameAccount::PUBLIC_SALE_STATUSES);
        }
        $query->search($data['q'] ?? '');
        if ($status) {
            $query->where('status', $status);
        }
        match ($data['sort'] ?? 'newest') {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        return view('catalog.index', [
            'accounts' => $query->orderByDesc('id')->paginate(12)->withQueryString(),
            'categories' => Category::withCount(['accounts' => fn ($q) => $q->where('status', 'available')])->orderBy('name')->get(),
            'selected' => $selected,
            'status' => $status,
        ]);
    }

    public function home(Request $request): View|RedirectResponse
    {
        if ($request->hasAny(['q', 'category', 'sort', 'available', 'page'])) {
            return redirect()->route('catalog.all', $request->query());
        }
        $availableForSale = GameAccount::with('category')->forSale()->where('status', 'available');

        return view('catalog.home', [
            'categories' => Category::withCount(['accounts' => fn ($q) => $q->where('status', 'available')])->orderBy('name')->get(),
            'catalogTotal' => GameAccount::forSale()->whereIn('status', GameAccount::PUBLIC_SALE_STATUSES)->count(),
            'available' => $availableForSale->latest()->orderByDesc('id')->limit(6)->get(),
            'flashSaleCount' => GameAccount::onFlashSale()->count(),
            'flashSale' => GameAccount::with('category')->onFlashSale()->orderBy('flash_sale_ends_at')->orderByDesc('id')->limit(4)->get(),
            'rentals' => GameAccount::with(['category', 'rentalPackages'])->rental()->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")->latest()->orderByDesc('id')->limit(4)->get(),
            'sold' => GameAccount::with('category')->forSale()->where('status', 'sold')->orderByDesc('sold_at')->orderByDesc('id')->limit(4)->get(),
            'infos' => InfoPost::ordered()->limit(12)->get(),
        ]);
    }

    public function rental(): View
    {
        return view('catalog.rental', ['accounts' => GameAccount::with(['category', 'rentalPackages'])->rental()
            ->orderByRaw("CASE WHEN status = 'available' THEN 0 ELSE 1 END")->latest()->orderByDesc('id')->paginate(12)]);
    }

    public function flashSale(): View
    {
        return view('catalog.flash-sale', ['accounts' => GameAccount::with('category')->onFlashSale()->orderBy('flash_sale_ends_at')->orderByDesc('id')->paginate(12)]);
    }

    public function show(Request $request, GameAccount $account): View
    {
        abort_if($account->status === 'draft', 404);
        $account->load('category', 'images', 'rentalPackages', 'owner');
        // "Dilihat": once per browser session, admins not counted. Raw increment so updated_at stays put.
        $viewed = $request->session()->get('viewed_accounts', []);
        if (! $request->user()?->is_admin && ! in_array($account->id, $viewed, true)) {
            GameAccount::whereKey($account->id)->toBase()->increment('view_count');
            $account->view_count++;
            $request->session()->put('viewed_accounts', array_slice([...$viewed, $account->id], -200));
        }
        $others = GameAccount::with('category')->forSale()->where('status', 'available')->whereKeyNot($account->id);
        $recommendations = $account->is_rental ? collect() : (clone $others)->where('category_id', $account->category_id)->latest()->orderByDesc('id')->limit(4)->get();
        if (! $account->is_rental && $recommendations->count() < 4) {
            $recommendations = $recommendations->concat((clone $others)->whereKeyNot($recommendations->modelKeys())->latest()->orderByDesc('id')->limit(4 - $recommendations->count())->get());
        }

        return view('catalog.show', compact('account', 'recommendations'));
    }

    /**
     * "Suka" counter: the wishlist button reports each add / remove. One like per account per browser session, so
     * clicking the heart again and again does not inflate the number.
     */
    public function like(Request $request, GameAccount $account): JsonResponse
    {
        abort_if($account->status === 'draft', 404);
        $liked = $request->validate(['liked' => 'required|boolean'])['liked'];
        $ids = $request->session()->get('liked_accounts', []);
        $counted = in_array($account->id, $ids, true);
        $query = GameAccount::whereKey($account->id)->toBase();
        if ($liked && ! $counted) {
            $query->increment('like_count');
            $ids = array_slice([...$ids, $account->id], -200);
        } elseif (! $liked && $counted) {
            $query->where('like_count', '>', 0)->decrement('like_count');
            $ids = array_values(array_diff($ids, [$account->id]));
        }
        $request->session()->put('liked_accounts', $ids);
        $likes = (int) GameAccount::whereKey($account->id)->value('like_count');

        return response()->json(['likes' => $likes, 'label' => GameAccount::compactCount($likes)]);
    }
}
