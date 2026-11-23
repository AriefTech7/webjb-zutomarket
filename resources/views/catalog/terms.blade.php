@extends('layouts.app')
@section('title', 'Ketentuan Layanan')
@section('description', 'Ketentuan layanan ZutoMarket untuk jual beli akun game, rental akun, dan joki.')
@section('content')
    @php
        $storeName = \App\Models\Setting::valueFor('store_name');
        $link = fn (string $route, string $label): string => '<a class="font-bold text-lime-300 hover:underline" href="'.e(route($route)).'">'.e($label).'</a>';
    @endphp
    <section class="shell max-w-3xl py-8 md:py-12">
        <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Home</a> / <span class="text-slate-300">Ketentuan Layanan</span></nav>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Ketentuan Layanan</h1>
        <p class="muted mt-2">Berlaku sejak 22 November 2026. Dengan memakai website {{ $storeName }} atau bertransaksi dengan kami, kamu dianggap telah membaca dan menyetujui ketentuan ini.</p>
        <div class="panel mt-6 space-y-6 p-6 text-sm leading-7 text-slate-300 md:p-8">
            <div>
                <h2 class="text-lg font-bold text-white">1. Tentang layanan</h2>
                <p class="mt-2">{{ $storeName }} adalah toko online untuk <strong class="text-white">jual beli akun game</strong> (Mobile Legends, Free Fire, PUBG Mobile, eFootball, Valorant, dan lainnya), <strong class="text-white">rental akun</strong>, serta <strong class="text-white">jasa joki rank Mobile Legends</strong>. Sebagian akun di katalog dititipkan oleh penjual mitra; pada akun tersebut pembeli dapat diarahkan menghubungi nomor WhatsApp penjual yang tercantum.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">2. Akun pengguna</h2>
                <p class="mt-2">Kamu wajib memberikan nama, email, dan nomor WhatsApp yang benar saat mendaftar atau memesan. Kamu bertanggung jawab menjaga kerahasiaan password akunmu di website ini.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">3. Produk dan harga</h2>
                <p class="mt-2">Deskripsi, spesifikasi, dan gambar akun dibuat berdasarkan kondisi akun saat diunggah. Harga tercantum dalam Rupiah. Stok bersifat unik (satu akun hanya satu) dan baru dianggap terjual setelah pembayaran dikonfirmasi admin; sebelum itu akun yang sama masih bisa dibeli orang lain.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">4. Pembayaran dan serah terima akun</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Pembayaran dilakukan melalui metode yang disediakan di website atau yang diinformasikan admin melalui WhatsApp resmi {{ $storeName }} (lihat halaman {!! $link('about', 'Tentang') !!}).</li>
                    <li>Harga akun dibayar <strong class="text-white">penuh (lunas) sekaligus</strong>. {{ $storeName }} tidak menyediakan pembelian dengan cicilan atau DP.</li>
                    <li>Setelah pembayaran terkonfirmasi, admin menyerahkan data login akun melalui live chat atau WhatsApp.</li>
                    <li>Pembeli wajib segera memeriksa akun, lalu mengganti password dan mengamankan email/nomor yang terhubung ke akun.</li>
                    <li>Transaksi di luar nomor admin resmi bukan tanggung jawab {{ $storeName }}. Selalu cek keaslian nomor di halaman {!! $link('about', 'Tentang') !!} dan {!! $link('reports', 'Laporan') !!}.</li>
                </ul>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">5. Rental akun</h2>
                <p class="mt-2">Penyewa hanya boleh memakai akun selama durasi paket yang dibayar. Penyewa dilarang mengganti password, email, nomor, atau ikatan login, memindahkan item, serta memakai cheat atau aplikasi pihak ketiga. Pelanggaran membuat sewa dihentikan tanpa pengembalian dana.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">6. Jasa joki</h2>
                <p class="mt-2">Pemesan joki memberikan data login akun secara sukarela untuk dikerjakan. Selama proses joki, pemilik akun tidak boleh login ke akun tersebut. Waktu pengerjaan adalah perkiraan dan dapat berubah tergantung kondisi permainan.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">7. Batasan tanggung jawab</h2>
                <p class="mt-2">Akun game tunduk pada aturan pengembang game masing-masing. Setelah data akun diserahkan dan diubah oleh pembeli, {{ $storeName }} tidak bertanggung jawab atas pemblokiran (banned), kehilangan item, atau masalah yang timbul dari aktivitas pembeli, termasuk memakai cheat atau membagikan data akun ke orang lain.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">8. Larangan</h2>
                <p class="mt-2">Dilarang memakai website untuk penipuan, mengirim laporan palsu, menyamar sebagai admin, atau mengganggu sistem. Kami berhak menolak transaksi dan menutup akun pengguna yang melanggar.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">9. Perubahan ketentuan</h2>
                <p class="mt-2">Ketentuan ini dapat diperbarui sewaktu-waktu. Versi terbaru selalu tersedia di halaman ini.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">10. Kontak</h2>
                <p class="mt-2">Pertanyaan tentang ketentuan ini dapat disampaikan melalui WhatsApp admin atau live chat. Lihat juga {!! $link('refund', 'Kebijakan Refund') !!} dan {!! $link('privacy', 'Kebijakan Privasi') !!}.</p>
            </div>
        </div>
    </section>
@endsection
