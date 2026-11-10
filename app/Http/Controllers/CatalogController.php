<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request, ?Category $category = null): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:255', 'sort' => 'nullable|in:newest,price_low,price_high', 'available' => 'nullable|in:1']);
        $selected = $category?->slug ?? ($data['category'] ?? '');
        $query = GameAccount::with('category')->forSale()->whereIn('status', ['available', 'sold']);
        if ($selected) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $selected));
        }
        $query->search($data['q'] ?? '');
        if ($request->boolean('available')) {
            $query->where('status', 'available');
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
            'flashSaleCount' => GameAccount::onFlashSale()->count(),
            'nextFlashSale' => GameAccount::onFlashSale()->orderBy('flash_sale_ends_at')->first(),
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

        return view('catalog.show', compact('account'));
    }
}
