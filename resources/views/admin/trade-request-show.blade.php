@extends('layouts.admin')
@section('title', 'Detail tukar tambah')
@section('admin-title', 'Detail pengajuan tukar tambah')
@section('admin-content')
<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <section class="panel space-y-6 p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><p class="eyebrow">{{ $tradeRequest->category->name }}</p><h2 class="mt-2 text-2xl font-bold">{{ $tradeRequest->name }}</h2><p class="muted mt-1">Dikirim {{ $tradeRequest->created_at->format('d M Y H:i') }}</p></div>
            <span class="status text-lime-300">{{ ['pending' => 'Pending', 'processing' => 'Diproses', 'accepted' => 'Diterima', 'rejected' => 'Ditolak', 'completed' => 'Selesai'][$tradeRequest->status] }}</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><p class="muted text-xs">WhatsApp</p><a class="font-semibold text-lime-300" href="{{ $tradeRequest->whatsappUrl() }}" target="_blank" rel="noopener noreferrer">{{ $tradeRequest->whatsapp_number }} ↗</a></div>
            <div><p class="muted text-xs">Email</p><p class="font-semibold">{{ $tradeRequest->email }}</p></div>
            <div><p class="muted text-xs">Estimasi harga</p><p class="font-semibold">{{ $tradeRequest->formattedEstimatedPrice() }}</p></div>
            <div><p class="muted text-xs">Akun yang ingin ditambah</p><p class="font-semibold">{{ $tradeRequest->desired_account ?: '-' }}</p></div>
        </div>
        <div><h3 class="mb-3 font-bold">Deskripsi akun</h3><p class="whitespace-pre-line leading-7 text-slate-300">{{ $tradeRequest->description }}</p></div>
        <div><h3 class="mb-3 font-bold">Screenshot akun</h3><div class="grid gap-3 sm:grid-cols-2">@foreach($tradeRequest->images as $image)<a href="{{ $image->imageUrl() }}" target="_blank" rel="noopener noreferrer" class="panel block overflow-hidden transition hover:border-lime-300/50"><img src="{{ $image->imageUrl() }}" alt="Screenshot akun {{ $loop->iteration }}" class="aspect-video w-full object-cover" loading="lazy"></a>@endforeach</div></div>
    </section>
    <aside class="space-y-5">
        @if($tradeRequest->status === 'completed')
            <div class="panel p-5"><p class="text-lime-300">Tukar tambah selesai dan sudah masuk laporan keuangan.</p><p class="muted mt-2 whitespace-pre-line text-sm">{{ $tradeRequest->admin_note }}</p><a class="btn-secondary mt-4" href="{{ route('admin.finance') }}">Lihat laporan keuangan ↗</a></div>
        @else
            <form method="POST" action="{{ route('admin.trade-requests.update', $tradeRequest) }}" class="panel space-y-5 p-6">
                @csrf @method('PUT')
                <div><label class="label" for="status">Status pengajuan</label><select class="field" id="status" name="status">@foreach(['pending' => 'Pending', 'processing' => 'Diproses', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'] as $value => $label)<option value="{{ $value }}" @selected($tradeRequest->status === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="label" for="admin_note">Catatan admin</label><textarea class="field" id="admin_note" name="admin_note" rows="7">{{ old('admin_note', $tradeRequest->admin_note) }}</textarea></div>
                <button class="btn w-full">Simpan update</button>
                <a class="btn-secondary w-full" href="{{ $tradeRequest->whatsappUrl() }}" target="_blank" rel="noopener noreferrer">Hubungi via WhatsApp ↗</a>
            </form>
            @if($tradeRequest->status === 'accepted')
                <a class="btn w-full" href="{{ route('admin.finance.create', ['type' => 'trade_in', 'trade_request' => $tradeRequest->id]) }}">Konfirmasi penyelesaian tukar tambah ↗</a>
                <p class="muted text-xs">Diterima belum berarti dibayar. Konfirmasikan setelah pembayaran dan serah terima selesai.</p>
            @endif
            <form method="POST" action="{{ route('admin.trade-requests.destroy', $tradeRequest) }}" data-confirm="Hapus pengajuan dan seluruh screenshot?">@csrf @method('DELETE')<button class="btn-secondary w-full text-rose-300">Hapus pengajuan</button></form>
        @endif
        <a class="btn-secondary w-full" href="{{ route('admin.trade-requests') }}">← Kembali ke daftar</a>
    </aside>
</div>
@endsection
