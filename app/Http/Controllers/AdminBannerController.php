<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The home page banner. Stored as settings (banner_image, banner_size, banner_link); without an upload
 * the built-in images/zuto-banner.webp is shown.
 */
class AdminBannerController extends Controller
{
    public const DEFAULT_IMAGE = 'images/zuto-banner.webp';

    /** @return array{url: string, width: int, height: int, link: string, custom: bool} */
    public static function current(): array
    {
        $path = Setting::valueFor('banner_image');
        $custom = $path !== '' && Storage::disk('public')->exists($path);
        [$width, $height] = $custom ? array_map(intval(...), explode('x', Setting::valueFor('banner_size', '1280x430')) + [1 => 430]) : [1280, 430];

        return [
            'url' => $custom ? Storage::disk('public')->url($path) : asset(self::DEFAULT_IMAGE),
            'width' => max($width, 1),
            'height' => max($height, 1),
            'link' => Setting::valueFor('banner_link'),
            'custom' => $custom,
        ];
    }

    public function edit(): View
    {
        return view('admin.banner', ['banner' => self::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=150'],
            'link_url' => ['nullable', 'url:https', 'max:500'],
        ], [
            'link_url.url' => 'Isi tautan lengkap yang diawali https://.',
            'image.dimensions' => 'Gambar terlalu kecil. Minimal lebar 600 px dan tinggi 150 px.',
        ]);
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            [$width, $height] = getimagesize($file->getRealPath()) ?: [1280, 430];
            $old = Setting::valueFor('banner_image');
            Setting::updateOrCreate(['key' => 'banner_image'], ['value' => $file->store('banner', 'public')]);
            Setting::updateOrCreate(['key' => 'banner_size'], ['value' => $width.'x'.$height]);
            if ($old !== '') {
                Storage::disk('public')->delete($old);
            }
        }
        Setting::updateOrCreate(['key' => 'banner_link'], ['value' => $data['link_url'] ?? '']);

        return back()->with('success', 'Banner berhasil disimpan.');
    }

    public function reset(): RedirectResponse
    {
        $old = Setting::valueFor('banner_image');
        if ($old !== '') {
            Storage::disk('public')->delete($old);
        }
        Setting::whereIn('key', ['banner_image', 'banner_size', 'banner_link'])->delete();

        return back()->with('success', 'Banner dikembalikan ke banner bawaan.');
    }
}
