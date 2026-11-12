<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactLink;
use App\Models\GameAccount;
use App\Models\InfoPost;
use App\Models\ManualSale;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        return view('admin.accounts', ['accounts' => GameAccount::with('category')->forSale()->search($data['q'] ?? '')->latest()->paginate(15)->withQueryString()]);
    }

    public function rentals(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $rentals = GameAccount::where('is_rental', true);

        return view('admin.rentals', [
            'accounts' => (clone $rentals)->with(['category', 'rentalPackages'])->search($data['q'] ?? '')->latest()->paginate(15)->withQueryString(),
            'readyCount' => (clone $rentals)->where('status', 'available')->count(),
            'rentedCount' => (clone $rentals)->where('status', 'rented')->count(),
        ]);
    }

    public function updateRentalStatus(Request $request, GameAccount $account): RedirectResponse
    {
        abort_unless($account->is_rental, 404);
        $data = $request->validate([
            'status' => 'required|in:available,rented',
            'ready_estimate_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);
        $account->update([
            'status' => $data['status'],
            'ready_estimate_at' => $data['status'] === 'rented' && ! empty($data['ready_estimate_at'])
                ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['ready_estimate_at'], GameAccount::LOCAL_TIMEZONE)->utc()
                : null,
        ]);

        return back()->with('success', $data['status'] === 'rented' ? 'Akun ditandai sedang disewa.' : 'Akun ditandai siap disewa.');
    }

    public function sales(Request $request): View
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = CarbonImmutable::createFromFormat('!Y-m', $data['month'] ?? now()->format('Y-m'));
        $allSold = GameAccount::where('status', 'sold')->whereNotNull('sold_at');
        $monthSold = (clone $allSold)->where('sold_at', '>=', $month)->where('sold_at', '<', $month->addMonth());
        $monthManual = ManualSale::where('sold_on', '>=', $month->toDateString())->where('sold_on', '<', $month->addMonth()->toDateString());

        $soldAccounts = (clone $monthSold)->with('category')->get();
        $manualSales = (clone $monthManual)->get();
        $rows = $soldAccounts->map(fn (GameAccount $account): array => [
            'date' => $account->sold_at->timezone(GameAccount::LOCAL_TIMEZONE)->startOfDay(), 'order' => $account->sold_at->getTimestamp(),
            'type' => 'Jual akun', 'source' => 'Katalog', 'item' => $account->title, 'note' => $account->category->name, 'amount' => $account->price, 'sale' => null,
        ])->concat($manualSales->map(fn (ManualSale $sale): array => [
            'date' => $sale->sold_on, 'order' => $sale->sold_on->getTimestamp() + $sale->id,
            'type' => $sale->typeLabel(), 'source' => 'Manual', 'item' => $sale->item, 'note' => $sale->note, 'amount' => $sale->amount, 'sale' => $sale,
        ]))->sortByDesc(fn (array $row): array => [$row['date']->format('Y-m-d'), $row['order']])->values();
        $breakdown = collect(ManualSale::TYPES)->map(fn (string $label, string $type): array => [
            'label' => $label,
            'count' => $manualSales->where('type', $type)->count() + ($type === 'jual_akun' ? $soldAccounts->count() : 0),
            'amount' => (int) $manualSales->where('type', $type)->sum('amount') + ($type === 'jual_akun' ? (int) $soldAccounts->sum('price') : 0),
        ]);
        $others = $manualSales->whereNotIn('type', array_keys(ManualSale::TYPES));
        if ($others->isNotEmpty()) {
            $breakdown->put('lainnya', ['label' => 'Lainnya', 'count' => $others->count(), 'amount' => (int) $others->sum('amount')]);
        }

        return view('admin.sales', [
            'month' => $month,
            'monthCount' => $rows->count(),
            'monthRevenue' => (int) $rows->sum('amount'),
            'totalCount' => (clone $allSold)->count() + ManualSale::count(),
            'totalRevenue' => (int) (clone $allSold)->sum('price') + (int) ManualSale::sum('amount'),
            'breakdown' => $breakdown,
            'rows' => $rows,
        ]);
    }

    /**
     * Price fields are typed as "1.000.000" (the admin form adds the dots). Turn plain rupiah text back
     * into digits before validation; anything else is left alone so the integer rule still rejects it.
     */
    private function normaliseMoney(Request $request, array $keys): void
    {
        $clean = fn ($value) => is_string($value) && preg_match('/^\s*(rp\.?)?\s*\d{1,3}([.\s]?\d{3})*\s*$/i', $value)
            ? preg_replace('/\D/', '', $value) : $value;
        foreach ($keys as $key) {
            if ($request->has($key)) {
                $request->merge([$key => $clean($request->input($key))]);
            }
        }
        if (is_array($packages = $request->input('packages'))) {
            $request->merge(['packages' => array_map(fn ($package) => is_array($package) && array_key_exists('price', $package)
                ? [...$package, 'price' => $clean($package['price'])] : $package, $packages)]);
        }
    }

    public function storeSale(Request $request): RedirectResponse
    {
        $this->normaliseMoney($request, ['amount']);
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ManualSale::TYPES))],
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
        $this->normaliseMoney($request, ['price', 'original_price']);
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

        return redirect()->route($account->is_rental ? 'admin.rentals' : 'admin.accounts')->with('success', 'Akun berhasil disimpan.');
    }

    public function destroy(GameAccount $account): RedirectResponse
    {
        $this->deleteAccounts(collect([$account]));

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => 'required|array|max:200', 'ids.*' => 'integer'], ['ids.required' => 'Pilih minimal satu akun untuk dihapus.']);
        $accounts = GameAccount::whereIn('id', array_unique($data['ids']))->get();
        $this->deleteAccounts($accounts);

        return back()->with('success', $accounts->count().' akun berhasil dihapus.');
    }

    /**
     * Delete the accounts with their rental packages and gallery rows, then remove their uploaded images.
     *
     * @param  Collection<int, GameAccount>  $accounts
     */
    private function deleteAccounts($accounts): void
    {
        $paths = DB::transaction(function () use ($accounts): array {
            $paths = [];
            foreach ($accounts as $account) {
                $account->refreshForUpdate();
                $paths = [...$paths, ...$account->images->pluck('image_path')->all()];
                if (! str_starts_with($account->main_image, 'demo/') && ! str_starts_with($account->main_image, 'images/')) {
                    $paths[] = $account->main_image;
                }
                $account->forceDelete();
            }

            return $paths;
        });
        Storage::disk('public')->delete($paths);
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

    public function infos(): View
    {
        return view('admin.infos', ['infos' => InfoPost::ordered()->get()]);
    }

    public function saveInfo(Request $request, ?InfoPost $info = null): RedirectResponse
    {
        $info ??= new InfoPost;
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'image' => [$info->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link_url' => 'nullable|url:https|max:500',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ], ['link_url.url' => 'Isi tautan lengkap yang diawali https://.']);
        $oldImage = $info->image_path;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('info', 'public');
        }
        unset($data['image']);
        $data['sort_order'] ??= 0;
        $info->fill($data)->save();
        if ($request->hasFile('image') && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', 'Info berhasil disimpan.');
    }

    public function deleteInfo(InfoPost $info): RedirectResponse
    {
        Storage::disk('public')->delete($info->image_path);
        $info->delete();

        return back()->with('success', 'Info berhasil dihapus.');
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
        $data = $request->validate(['store_name' => 'required|string|max:80', 'whatsapp_number' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'], 'whatsapp_rental_number' => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'], 'feedback_email' => 'nullable|email|max:120', 'whatsapp_default_message' => 'required|string|max:1000', 'running_text' => 'nullable|string|max:1000', 'accent_color' => 'sometimes|in:default,deepskyblue']);
        // Saved as text (one item per line); an empty value hides the running text instead of restoring the default.
        if ($request->exists('running_text')) {
            $data['running_text'] = trim(preg_replace("/\r\n?/", "\n", $data['running_text'] ?? ''));
        }
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
