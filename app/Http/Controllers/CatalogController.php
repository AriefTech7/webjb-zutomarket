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
        $query = GameAccount::with('category')->whereIn('status', ['available', 'sold']);
        if ($selected) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $selected));
        }
        foreach (preg_split('/\s+/', trim($data['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $keyword) {
            $query->where(function ($q) use ($keyword) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
                $q->where('title', 'like', $term)->orWhere('description', 'like', $term)->orWhere('specifications', 'like', $term)->orWhereHas('category', fn ($c) => $c->where('name', 'like', $term));
            });
        }
        if ($request->boolean('available')) {
            $query->where('status', 'available');
        }
        match ($data['sort'] ?? 'newest') {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        return view('catalog.index', ['accounts' => $query->orderByDesc('id')->paginate(12)->withQueryString(), 'categories' => Category::withCount(['accounts' => fn ($q) => $q->where('status', 'available')])->orderBy('name')->get(), 'selected' => $selected]);
    }

    public function show(GameAccount $account): View
    {
        abort_if($account->status === 'draft', 404);
        $account->load('category', 'images');

        return view('catalog.show', compact('account'));
    }
}
