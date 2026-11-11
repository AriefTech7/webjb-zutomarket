<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\InfoPost;
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
        $query = GameAccount::with('category')->forSale()->whereIn('status', ['available', 'sold']);
        if ($selected) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $selected));
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
            'categories' => Category::withCount(['accounts' => fn ($q) => $q->forSale()->where('status', 'available')])->orderBy('name')->get(),
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
            'categories' => Category::withCount(['accounts' => fn ($q) => $q->forSale()->where('status', 'available')])->orderBy('name')->get(),
            'catalogTotal' => GameAccount::forSale()->whereIn('status', ['available', 'sold'])->count(),
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

    public function show(GameAccount $account): View
    {
        abort_if($account->status === 'draft', 404);
        $account->load('category', 'images', 'rentalPackages');
        $others = GameAccount::with('category')->forSale()->where('status', 'available')->whereKeyNot($account->id);
        $recommendations = $account->is_rental ? collect() : (clone $others)->where('category_id', $account->category_id)->latest()->orderByDesc('id')->limit(4)->get();
        if (! $account->is_rental && $recommendations->count() < 4) {
            $recommendations = $recommendations->concat((clone $others)->whereKeyNot($recommendations->modelKeys())->latest()->orderByDesc('id')->limit(4 - $recommendations->count())->get());
        }

        return view('catalog.show', compact('account', 'recommendations'));
    }
}
