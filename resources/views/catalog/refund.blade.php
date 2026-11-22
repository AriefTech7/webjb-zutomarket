@extends('layouts.app')
@section('title', 'Kebijakan Refund')
@section('description', 'Kebijakan pengembalian dana (refund) ZutoMarket untuk pembelian akun game, cicilan, rental, dan joki.')
@section('content')
    @php
        $storeName = \App\Models\Setting::valueFor('store_name');
        $link = fn (string $route, string $label): string => '<a class="font-bold text-lime-300 hover:underline" href="'.e(route($route)).'">'.e($label).'</a>';
    @endphp
    <section class="shell max-w-3xl py-8 md:py-12">
        <nav class="muted text-xs md:text-sm" aria-label="Breadcrumb"><a class="hover:text-white" href="{{ route('catalog') }}">Home</a> / <span class="text-slate-300">Kebijakan Refund</span></nav>
        <h1 class="mt-3 text-3xl font-black md:text-4xl">Kebijakan Refund</h1>
        <p class="muted mt-2">Berlaku sejak 22 November 2026. Kebijakan ini menjelaskan kapan dana pembelian di {{ $storeName }} dapat dikembalikan dan bagaimana cara mengajukannya.</p>
        <div class="panel mt-6 space-y-6 p-6 text-sm leading-7 text-slate-300 md:p-8">
            <div>
                <h2 class="text-lg font-bold text-white">1. Refund penuh</h2>
                <p class="mt-2">Dana dikembalikan 100% apabila:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Akun yang sudah kamu bayar ternyata sudah terjual atau tidak tersedia, dan kamu tidak ingin menggantinya dengan akun lain.</li>
                    <li>Admin tidak dapat menyerahkan data login akun setelah pembayaran terkonfirmasi.</li>
                    <li>Kondisi akun tidak sesuai dengan deskripsi atau spesifikasi di halaman akun, dilaporkan paling lambat <strong class="text-white">1×24 jam</strong> setelah serah terima dan sebelum data login akun diubah.</li>
                    <li>Pembayaran ganda atau kelebihan bayar (selisihnya dikembalikan).</li>
                </ul>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">2. Cicilan dan DP</h2>
                <p class="mt-2">Jika pelunasan melewati batas waktu atau pembeli membatalkan secara sepihak, DP yang sudah dibayar dikenakan potongan sesuai ketentuan di halaman {!! $link('installment', 'Cicilan & DP') !!}, lalu akun ditarik kembali dan sisa dana (jika ada) dikembalikan. Jika pembatalan terjadi karena kesalahan toko, DP dikembalikan penuh.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">3. Rental akun</h2>
                <p class="mt-2">Sewa yang sudah berjalan tidak dapat di-refund. Jika akun sewaan bermasalah dari sisi toko sehingga tidak bisa dipakai, kami mengganti dengan akun lain atau mengembalikan dana untuk sisa durasi yang belum terpakai.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">4. Jasa joki</h2>
                <p class="mt-2">Jika joki belum dimulai, dana dikembalikan penuh. Jika joki tidak dapat diselesaikan oleh pihak kami, dana dikembalikan sebanding dengan bintang atau paket yang belum dikerjakan. Pembatalan karena pemilik akun login di tengah proses atau memberikan data akun yang salah tidak mendapat refund untuk bagian yang sudah dikerjakan.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">5. Tidak dapat di-refund</h2>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Berubah pikiran setelah data akun diserahkan.</li>
                    <li>Data login akun sudah diubah, dijual ulang, atau dibagikan ke orang lain.</li>
                    <li>Akun terkena banned atau kehilangan item karena aktivitas pembeli setelah serah terima.</li>
                    <li>Pembayaran yang dilakukan ke nomor atau rekening yang bukan milik admin resmi {{ $storeName }}.</li>
                </ul>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">6. Cara mengajukan refund</h2>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>Hubungi admin resmi melalui WhatsApp (lihat halaman {!! $link('about', 'Tentang') !!}) atau live chat di website.</li>
                    <li>Sertakan nama akun yang dibeli, bukti pembayaran, dan alasan refund beserta bukti (screenshot/rekaman layar).</li>
                    <li>Admin memeriksa pengajuan dan memberi jawaban paling lambat 1×24 jam.</li>
                    <li>Refund yang disetujui dikirim ke rekening bank atau e-wallet atas nama pembeli dalam <strong class="text-white">1–3 hari kerja</strong>.</li>
                </ol>
                <p class="mt-2">Biaya transaksi dari bank atau penyedia pembayaran yang sudah terpotong saat pembayaran tidak ikut dikembalikan, kecuali refund terjadi karena kesalahan toko.</p>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white">7. Kontak</h2>
                <p class="mt-2">Ada pertanyaan tentang refund? Hubungi admin melalui halaman {!! $link('about', 'Tentang') !!}. Lihat juga {!! $link('terms', 'Ketentuan Layanan') !!}.</p>
            </div>
        </div>
    </section>
@endsection
