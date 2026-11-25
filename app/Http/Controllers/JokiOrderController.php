<?php

namespace App\Http\Controllers;

use App\Services\JokiPricing;
use App\Services\Tripay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Joki paid on the website: the /joki-ml form posts here, the price is worked out again on the server from
 * config/joki.php, and the brief is kept in the session for the checkout page (/checkout?joki=1). The game
 * password is never sent here: the admin asks for it in the live chat after payment.
 */
class JokiOrderController extends Controller
{
    public const SESSION_KEY = 'joki_draft';

    /** Guests who press "Bayar di website" sign in first and come back to the joki page. */
    public function signIn(): RedirectResponse
    {
        return redirect()->route('joki');
    }

    public function prepare(Request $request): RedirectResponse
    {
        abort_if($request->user()->is_admin, 403, 'Akun admin tidak bisa checkout. Masuk dengan akun pembeli.');
        if (! Tripay::active()) {
            return redirect()->route('joki')->with('error', 'Pembayaran di website sedang tidak aktif. Pesan joki lewat WhatsApp.');
        }
        $services = config('joki.services');
        $data = $request->validate([
            'mode' => ['required', Rule::in(['calculator', 'package'])],
            'service' => ['required_if:mode,calculator', 'nullable', Rule::in(array_keys($services))],
            'fromRank' => ['required_if:mode,calculator', 'nullable', 'string', 'max:40'],
            'fromStars' => ['required_if:mode,calculator', 'nullable', 'integer', 'min:0', 'max:1000'],
            'toRank' => ['required_if:mode,calculator', 'nullable', 'string', 'max:40'],
            'toStars' => ['required_if:mode,calculator', 'nullable', 'integer', 'min:0', 'max:1000'],
            'role' => ['nullable', Rule::in(config('joki.roles'))],
            'packages' => ['required_if:mode,package', 'array', 'max:10'],
            'packages.*' => ['string', 'max:80'],
            'heroes' => ['nullable', 'array', 'max:10'],
            'heroes.*' => ['nullable', 'string', 'max:40'],
            'userId' => ['required', 'string', 'max:30'],
            'serverId' => ['nullable', 'string', 'max:10'],
            'loginVia' => ['nullable', Rule::in(config('joki.login_methods'))],
            'loginId' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:200'],
            'contactName' => ['required', 'string', 'max:60'],
            'contactPhone' => ['required', 'string', 'max:20', 'regex:/^[+\d][\d\s\-]{5,19}$/'],
        ], ['packages.required_if' => 'Tambahkan minimal satu paket joki.']);

        $heroes = array_values(array_filter(array_map('trim', $data['heroes'] ?? [])));
        if ($data['mode'] === 'package') {
            $lines = [];
            foreach (array_unique($data['packages']) as $name) {
                $price = JokiPricing::packagePrice($name);
                if ($price === null) {
                    return back()->with('error', 'Paket joki "'.$name.'" tidak ditemukan.');
                }
                $lines[] = ['name' => $name, 'price' => $price];
            }
            $title = 'Paket joki ML: '.implode(', ', array_column($lines, 'name'));
            $price = array_sum(array_column($lines, 'price'));
            $brief = ['Paket' => implode(', ', array_map(fn (array $line): string => $line['name'].' ('.number_format($line['price'], 0, ',', '.').')', $lines))];
        } else {
            $service = $services[$data['service']];
            $quote = JokiPricing::fromConfig()->quote($data['fromRank'], (int) $data['fromStars'], $data['toRank'], (int) $data['toStars'], (int) $service['extra']);
            if (! $quote) {
                return back()->with('error', 'Rank tujuan harus lebih tinggi dari rank saat ini.');
            }
            $title = $service['label'].' ML: '.$quote['from'].' → '.$quote['to'];
            $price = $quote['total'];
            $brief = ['Layanan' => $service['label'], 'Rank' => $quote['from'].' → '.$quote['to'].' ('.$quote['stars'].' bintang)'];
            if ($data['service'] === 'role' && filled($data['role'] ?? null)) {
                $brief['Role prioritas'] = $data['role'];
            }
        }
        $brief += array_filter([
            'Request hero' => implode(', ', $heroes),
            'ID akun' => trim($data['userId']).(filled($data['serverId'] ?? null) ? ' ('.trim($data['serverId']).')' : ''),
            'Masuk melalui' => $data['loginVia'] ?? null,
            'Email / No. HP / Moonton ID' => $data['loginId'] ?? null,
            'Catatan' => $data['notes'] ?? null,
        ]);

        $request->session()->put(self::SESSION_KEY, [
            'title' => mb_substr($title, 0, 250),
            'price' => (int) $price,
            'details' => $brief,
            'contact' => ['name' => $data['contactName'], 'phone' => $data['contactPhone']],
        ]);

        return redirect()->route('checkout', ['joki' => 1]);
    }
}
