<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\Setting;
use App\Models\TradeRequest;
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
        return view('admin.dashboard', ['total' => GameAccount::count(), 'categories' => Category::count(), 'available' => GameAccount::where('status', 'available')->count(), 'sold' => GameAccount::where('status', 'sold')->count(), 'tradeRequests' => TradeRequest::where('status', 'pending')->count(), 'accounts' => GameAccount::with('category')->latest()->limit(5)->get()]);
    }

    public function accounts(): View
    {
        return view('admin.accounts', ['accounts' => GameAccount::with('category')->latest()->paginate(15)]);
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
            'title'         => 'required|string|max:150', 'slug'             => ['required', 'max:180', Rule::unique('game_accounts')->ignore($account->id)],
            'category_id'   => 'required|exists:categories,id', 'price'      => 'required|integer|min:0|max:999999999999',
            'description'   => 'required|string|max:10000', 'specifications' => 'required|string|max:10000',
            'status'        => 'required|in:available,sold,draft',
            'main_image'    => [$account->exists ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'images'        => 'nullable|array|max:6', 'images.*'            => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_images' => 'nullable|array', 'remove_images.*'           => 'integer',
        ]);
        $deletedImages = $account->images()->whereIn('id', $data['remove_images'] ?? [])->get();
        if ($account->images()->count() - $deletedImages->count() + count($request->file('images', [])) > 6) {
            return back()->withErrors(['images' => 'Maksimal 6 gambar tambahan per akun.'])->withInput();
        }
        unset($data['images'], $data['remove_images'], $data['main_image']);
        $newPaths = [];
        $oldMain  = $account->main_image;
        try {
            DB::transaction(function () use ($request, $account, $data, $deletedImages, &$newPaths) {
                if ($request->hasFile('main_image')) {
                    $data['main_image'] = $request->file('main_image')->store('accounts', 'public');
                    $newPaths[]         = $data['main_image'];
                }
                $account->fill($data)->save();
                foreach ($request->file('images', []) as $image) {
                    $path       = $image->store('accounts', 'public');
                    $newPaths[] = $path;
                    $account->images()->create(['image_path' => $path]);
                }
                $account->images()->whereIn('id', $deletedImages->modelKeys())->delete();
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPaths);
            throw $exception;
        }
        Storage::disk('public')->delete($deletedImages->pluck('image_path')->all());
        if ($request->hasFile('main_image') && $oldMain && ! str_starts_with($oldMain, 'demo/')) {
            Storage::disk('public')->delete($oldMain);
        }

        return redirect()->route('admin.accounts')->with('success', 'Akun berhasil disimpan.');
    }

    public function destroy(GameAccount $account): RedirectResponse
    {
        $paths = $account->images->pluck('image_path')->all();
        if (! str_starts_with($account->main_image, 'demo/')) {
            $paths[] = $account->main_image;
        }
        $account->delete();
        Storage::disk('public')->delete($paths);

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function categories(): View
    {
        return view('admin.categories', ['categories' => Category::withCount('accounts')->orderBy('name')->get()]);
    }

    public function tradeRequests(): View
    {
        return view('admin.trade-requests', ['requests' => TradeRequest::with('category')->latest()->paginate(15)]);
    }

    public function tradeRequest(TradeRequest $tradeRequest): View
    {
        return view('admin.trade-request-show', ['tradeRequest' => $tradeRequest->load('category', 'images')]);
    }

    public function updateTradeRequest(Request $request, TradeRequest $tradeRequest): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:pending,processing,accepted,rejected', 'admin_note' => 'nullable|string|max:5000']);
        $tradeRequest->update($data);

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }

    public function deleteTradeRequest(TradeRequest $tradeRequest): RedirectResponse
    {
        $paths = $tradeRequest->images->pluck('image_path')->all();
        $tradeRequest->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('admin.trade-requests')->with('success', 'Pengajuan tukar tambah berhasil dihapus.');
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
        if ($category->accounts()->exists()) {
            return back()->withErrors(['category' => 'Kategori masih digunakan. Pindahkan atau hapus akun di kategori ini terlebih dahulu.']);
        }
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    public function settings(): View
    {
        return view('admin.settings');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['store_name' => 'required|string|max:80', 'whatsapp_number' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'], 'whatsapp_default_message' => 'required|string|max:1000']);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
