<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TradeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TradeRequestController extends Controller
{
    public function create(): View
    {
        return view('trade.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'whatsapp_number' => ['required', 'regex:/^[1-9][0-9]{7,14}$/'],
            'email' => 'required|email|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|min:20|max:10000',
            'estimated_price' => 'nullable|integer|min:0|max:999999999999',
            'desired_account' => 'nullable|string|max:180',
            'screenshots' => 'required|array|min:1|max:5',
            'screenshots.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'screenshots.required' => 'Minimal upload 1 screenshot akun game.',
            'screenshots.max' => 'Maksimal 5 screenshot akun game.',
            'screenshots.*.uploaded' => 'Screenshot gagal diupload. Maksimal 4 MB per gambar. Silakan pilih ulang gambar.',
            'screenshots.*.max' => 'Ukuran setiap screenshot maksimal 4 MB.',
            'screenshots.*.image' => 'Screenshot harus berupa gambar yang valid.',
            'screenshots.*.mimes' => 'Format screenshot harus JPG, PNG, atau WebP.',
            'whatsapp_number.regex' => 'Nomor WhatsApp harus menggunakan format 628xxxxxxxxxx tanpa +, spasi, atau tanda hubung.',
        ]);
        unset($data['screenshots']);

        $paths = [];
        try {
            DB::transaction(function () use ($request, $data, &$paths) {
                $tradeRequest = TradeRequest::create($data + ['status' => 'pending']);
                foreach ($request->file('screenshots', []) as $screenshot) {
                    $path = $screenshot->store('trade-requests', 'public');
                    if ($path === false) {
                        throw new \RuntimeException('Gagal menyimpan screenshot pengajuan.');
                    }
                    $paths[] = $path;
                    $tradeRequest->images()->create(['image_path' => $path]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            report($exception);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pengajuan belum tersimpan. Penyimpanan gambar sedang bermasalah, silakan coba lagi.'], 503);
            }

            return back()->withInput($request->except('screenshots'))->withErrors(['screenshots' => 'Pengajuan belum tersimpan. Gagal menyimpan gambar, silakan coba lagi.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Pengajuan tukar tambah berhasil dikirim. Admin akan menghubungi Anda melalui WhatsApp.'], 201);
        }

        return redirect()->route('trade.create')->with('success', 'Pengajuan tukar tambah berhasil dikirim. Admin akan menghubungi Anda melalui WhatsApp.');
    }
}
