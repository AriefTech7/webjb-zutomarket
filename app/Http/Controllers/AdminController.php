<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactLink;
use App\Models\GameAccount;
use App\Models\ManualSale;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', ['total' => GameAccount::count(), 'categories' => Category::count(), 'available' => GameAccount::where('status', 'available')->count(), 'sold' => GameAccount::where('status', 'sold')->count(), 'accounts' => GameAccount::with('category')->latest()->limit(5)->get()]);
    }

    public function accounts(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);

        return view('admin.accounts', ['accounts' => GameAccount::with('category')->search($data['q'] ?? '')->latest()->paginate(15)->withQueryString()]);
    }

    public function sales(Request $request): View
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $data['month'] ?? now()->format('Y-m'));
        $allSold = GameAccount::where('status', 'sold')->whereNotNull('sold_at');
        $monthSold = (clone $allSold)->where('sold_at', '>=', $month)->where('sold_at', '<', $month->addMonth());
        $monthManual = ManualSale::where('sold_on', '>=', $month->toDateString())->where('sold_on', '<', $month->addMonth()->toDateString());

        return view('admin.sales', [
            'month' => $month,
            'monthCount' => (clone $monthSold)->count() + (clone $monthManual)->count(),
            'monthRevenue' => (int) (clone $monthSold)->sum('price') + (int) (clone $monthManual)->sum('amount'),
            'totalCount' => (clone $allSold)->count() + ManualSale::count(),
            'totalRevenue' => (int) (clone $allSold)->sum('price') + (int) ManualSale::sum('amount'),
            'accounts' => $monthSold->with('category')->latest('sold_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'manualSales' => $monthManual->orderByDesc('sold_on')->orderByDesc('id')->paginate(20, ['*'], 'manual_page')->withQueryString(),
        ]);
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item' => 'required|string|max:150',
            'amount' => 'required|integer|min:1|max:999999999999',
            'sold_on' => 'required|date_format:Y-m-d',
            'note' => 'nullable|string|max:255',
        ]);
        $sale = ManualSale::create($data);

        return redirect()->route('admin.sales', ['month' => $sale->sold_on->format('Y-m')])->with('success', 'Penjualan berhasil dicatat.');
    }

    public function destroySale(ManualSale $manualSale): RedirectResponse
    {
        $manualSale->delete();

        return back()->with('success', 'Catatan penjualan berhasil dihapus.');
    }

    public function form(?GameAccount $account = null): View
    {
        return view('admin.account-form', ['account' => $account ?? new GameAccount, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveAccount($request, new GameAccount);
    }

    public function update(Request $request, GameAccount $account): RedirectResponse
    {
        return $this->saveAccount($request, $account);
    }

    private function saveAccount(Request $request, GameAccount $account): RedirectResponse
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title', ''))]);
        $data = $request->validate([
            'title' => 'required|string|max:150', 'slug' => ['required', 'max:180', Rule::unique('game_accounts')->ignore($account->id)],
            'category_id' => 'required|exists:categories,id', 'price' => 'required_unless:is_rental,1|nullable|integer|min:0|max:999999999999',
            'is_rental' => 'nullable|boolean',
            'packages' => 'nullable|array|max:6',
            'packages.*.duration' => 'nullable|string|max:50|required_with:packages.*.price',
            'packages.*.price' => 'nullable|integer|min:0|max:999999999999|required_with:packages.*.duration',
            'ready_estimate_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'description' => 'required|string|max:10000', 'specifications' => 'required|string|max:10000',
            'status' => 'required|in:available,sold,draft,rented',
            'seller_whatsapp' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
            'original_price' => ['nullable', 'integer', 'gt:price', 'max:999999999999'],
            'flash_sale_ends_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'main_image' => [$account->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'images' => 'nullable|array|max:6', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_images' => 'nullable|array', 'remove_images.*' => 'integer',
        ], [
            'seller_whatsapp.regex' => 'Nomor penjual harus diawali kode negara tanpa tanda +, contoh 6281234567890.',
            'original_price.gt' => 'Harga coret harus lebih besar dari harga jual.',
        ]);
        if (! empty($data['flash_sale_ends_at'])) {
            $data['flash_sale_ends_at'] = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['flash_sale_ends_at'], GameAccount::LOCAL_TIMEZONE)->utc();
        }
        $data['is_rental'] = $request->boolean('is_rental');
        $packages = collect($data['packages'] ?? [])->filter(fn (array $package): bool => filled($package['duration'] ?? null))->values();
        if ($data['is_rental'] && $packages->isEmpty()) {
            return back()->withErrors(['packages' => 'Isi minimal satu paket sewa untuk akun rental.'])->withInput();
        }
        if ($data['status'] === 'rented' && ! $data['is_rental']) {
            return back()->withErrors(['status' => 'Status "Sedang disewa" hanya untuk akun rental.'])->withInput();
        }
        if ($data['is_rental']) {
            $data['price'] = (int) $packages->min('price');
        }
        $data['ready_estimate_at'] = $data['is_rental'] && ! empty($data['ready_estimate_at'])
            ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['ready_estimate_at'], GameAccount::LOCAL_TIMEZONE)->utc()
            : null;
        unset($data['packages']);
        $deletedImages = $account->images()->whereIn('id', $data['remove_images'] ?? [])->get();
        if ($account->images()->count() - $deletedImages->count() + count($request->file('images', [])) > 6) {
            return back()->withErrors(['images' => 'Maksimal 6 gambar tambahan per akun.'])->withInput();
        }
        unset($data['images'], $data['remove_images'], $data['main_image']);
        $newPaths = [];
        $oldMain = $account->main_image;
        try {
            DB::transaction(function () use ($request, $account, $data, $deletedImages, $packages, &$newPaths) {
                if ($account->exists) {
                    $account->refreshForUpdate();
                }
                if ($request->hasFile('main_image')) {
                    $data['main_image'] = $request->file('main_image')->store('accounts', 'public');
                    $newPaths[] = $data['main_image'];
                }
                $account->fill($data)->save();
                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('accounts', 'public');
                    $newPaths[] = $path;
                    $account->images()->create(['image_path' => $path]);
                }
                $account->images()->whereIn('id', $deletedImages->modelKeys())->delete();
                $account->rentalPackages()->delete();
                if ($account->is_rental) {
                    foreach ($packages as $index => $package) {
                        $account->rentalPackages()->create(['duration' => $package['duration'], 'price' => (int) $package['price'], 'sort_order' => $index]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }
        Storage::disk('public')->delete($deletedImages->pluck('image_path')->all());
        if ($request->hasFile('main_image') && $oldMain && ! str_starts_with($oldMain, 'demo/') && ! str_starts_with($oldMain, 'images/')) {
            Storage::disk('public')->delete($oldMain);
        }

        return redirect()->route('admin.accounts')->with('success', 'Akun berhasil disimpan.');
    }

    public function destroy(GameAccount $account): RedirectResponse
    {
        $paths = DB::transaction(function () use ($account): array {
            $account->refreshForUpdate();
            $paths = $account->images->pluck('image_path')->all();
            if (! str_starts_with($account->main_image, 'demo/') && ! str_starts_with($account->main_image, 'images/')) {
                $paths[] = $account->main_image;
            }
            $account->forceDelete();

            return $paths;
        });
        Storage::disk('public')->delete($paths);

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function categories(): View
    {
        return view('admin.categories', ['categories' => Category::withCount('accounts')->orderBy('name')->get()]);
    }

    public function saveCategory(Request $request, ?Category $category = null): RedirectResponse
    {
        $category ??= new Category;
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name', ''))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category->id)], 'slug' => ['required', 'max:120', Rule::unique('categories')->ignore($category->id)]]);
        $category->fill($data)->save();

        return back()->with('success', 'Kategori berhasil disimpan.');
    }

    public function deleteCategory(Category $category): RedirectResponse
    {
        if ($category->accounts()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Kategori masih digunakan. Pindahkan atau hapus akun di kategori ini terlebih dahulu.']);
        }
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    public function contacts(): View
    {
        return view('admin.contacts', ['contacts' => ContactLink::orderBy('sort_order')->orderBy('id')->get()->groupBy('group')]);
    }

    public function saveContact(Request $request, ?ContactLink $contact = null): RedirectResponse
    {
        $contact ??= new ContactLink;
        $data = $request->validate([
            'group' => ['required', Rule::in(array_keys(ContactLink::GROUPS))],
            'title' => 'required|string|max:80',
            'subtitle' => 'nullable|string|max:120',
            'value' => ['required', 'string', 'max:500', $request->input('group') === 'admin' ? 'regex:/^[1-9][0-9]{7,14}$/' : 'url:https'],
            'badge' => 'nullable|string|max:12',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ], ['value.regex' => 'Nomor WhatsApp harus diawali kode negara tanpa tanda +, contoh 6281234567890.', 'value.url' => 'Isi tautan lengkap yang diawali https://.']);
        $data['sort_order'] ??= 0;
        $contact->fill($data)->save();

        return back()->with('success', 'Kontak berhasil disimpan.');
    }

    public function deleteContact(ContactLink $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('success', 'Kontak berhasil dihapus.');
    }

    public function settings(): View
    {
        return view('admin.settings');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['store_name' => 'required|string|max:80', 'whatsapp_number' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'], 'whatsapp_rental_number' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'], 'whatsapp_default_message' => 'required|string|max:1000']);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
