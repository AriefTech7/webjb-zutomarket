<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\Voucher;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Admin > Voucher (super admin): create discount codes for the website checkout, switch them on/off, delete. */
class AdminVoucherController extends Controller
{
    public function index(): View
    {
        return view('admin.vouchers', [
            'vouchers' => Voucher::withCount(['countedOrders as used_count'])->latest('id')->paginate(20),
            'suggestion' => 'ZUTO'.Str::upper(Str::random(5)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Rupiah fields may be typed as "50.000" like the other money inputs.
        $request->merge(collect(['value', 'max_discount', 'min_purchase'])->mapWithKeys(fn (string $key): array => [
            $key => is_string($request->input($key)) && preg_match('/^(Rp\s*)?[\d.\s]+$/i', trim($request->input($key))) ? preg_replace('/\D/', '', $request->input($key)) : $request->input($key),
        ])->all() + ['code' => Voucher::normaliseCode($request->input('code'))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('vouchers', 'code')],
            'type' => ['required', Rule::in(array_keys(Voucher::TYPES))],
            'value' => ['required', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:100' : 'max:999999999'],
            'max_discount' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'min_purchase' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_user' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i', ...($request->filled('starts_at') ? ['after:starts_at'] : [])],
            'note' => ['nullable', 'string', 'max:120'],
        ], [
            'code.regex' => 'Kode voucher hanya boleh huruf, angka, tanda - dan _ (tanpa spasi).',
            'code.unique' => 'Kode voucher ini sudah dipakai.',
            'value.max' => 'Potongan persen maksimal 100.',
            'ends_at.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ]);
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data[$key], GameAccount::LOCAL_TIMEZONE)->utc() : null;
        }
        if ($data['type'] === 'fixed') {
            $data['max_discount'] = null;
        }
        $data['min_purchase'] = (int) ($data['min_purchase'] ?? 0);
        $data['per_user'] = $data['per_user'] ?? null;
        Voucher::create($data + ['is_active' => true]);

        return back()->with('success', 'Voucher '.$data['code'].' dibuat dan langsung aktif.');
    }

    public function toggle(Voucher $voucher): RedirectResponse
    {
        $voucher->update(['is_active' => ! $voucher->is_active]);

        return back()->with('success', 'Voucher '.$voucher->code.($voucher->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();

        return back()->with('success', 'Voucher '.$voucher->code.' dihapus. Pesanan yang sudah memakainya tetap tercatat.');
    }
}
