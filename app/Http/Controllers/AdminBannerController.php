<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Home page banner slider. Without any banner the built-in images/zuto-banner.webp is shown.
 */
class AdminBannerController extends Controller
{
    public const MAX_BANNERS = 10;

    public function index(): View
    {
        return view('admin.banner', ['banners' => Banner::ordered()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (Banner::count() >= self::MAX_BANNERS) {
            return back()->withErrors(['image' => 'Maksimal '.self::MAX_BANNERS.' banner. Hapus salah satu dulu.']);
        }
        $data = $this->validated($request, true);
        Banner::create([...$this->storeImage($request->file('image')), 'link_url' => $data['link_url'] ?? null, 'sort_order' => $data['sort_order'] ?? Banner::max('sort_order') + 1]);

        return back()->with('success', 'Banner berhasil ditambahkan.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validated($request, false);
        $old = $banner->image_path;
        $image = $request->hasFile('image') ? $this->storeImage($request->file('image')) : [];
        $banner->update([...$image, 'link_url' => $data['link_url'] ?? null, 'sort_order' => $data['sort_order'] ?? $banner->sort_order]);
        if ($image) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Banner berhasil disimpan.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();

        return back()->with('success', 'Banner dihapus.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=150'],
            'link_url' => ['nullable', 'url:https', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'image.required' => 'Pilih gambar banner.',
            'link_url.url' => 'Isi tautan lengkap yang diawali https://.',
            'image.dimensions' => 'Gambar terlalu kecil. Minimal lebar 600 px dan tinggi 150 px.',
        ]);
    }

    /** @return array{image_path: string, width: int, height: int} */
    private function storeImage(UploadedFile $file): array
    {
        [$width, $height] = getimagesize($file->getRealPath()) ?: [1280, 430];

        return ['image_path' => $file->store('banner', 'public'), 'width' => $width, 'height' => $height];
    }
}
