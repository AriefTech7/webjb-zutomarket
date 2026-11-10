@extends('layouts.admin')
@section('title', 'Kontak & Sosmed')
@section('admin-title', 'Kontak & Sosmed')
@section('admin-content')
    <p class="muted mb-5">Kontak di sini tampil di halaman Tentang, dikelompokkan dalam tab WA Admin, Sosmed, dan Saluran. Urutan kecil tampil lebih dulu.</p>
    <form action="{{ route('admin.contacts.store') }}" method="POST" class="panel mb-8 grid gap-4 p-5 md:grid-cols-2 md:p-6">
        @csrf
        <h2 class="text-lg font-bold md:col-span-2">Tambah kontak</h2>
        <div><label class="label" for="new-group">Jenis</label><select class="field" id="new-group" name="group" required>
                @foreach (\App\Models\ContactLink::GROUPS as $value => $label)
                    <option value="{{ $value }}" @selected(old('group') === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
        <div><label class="label" for="new-title">Nama</label><input class="field" id="new-title" name="title" value="{{ old('title') }}" maxlength="80" required placeholder="Contoh: Admin Free Fire"></div>
        <div><label class="label" for="new-value">Nomor WhatsApp atau tautan</label><input class="field" id="new-value" name="value" value="{{ old('value') }}" maxlength="500" required placeholder="6281234567890 atau https://...">
            <p class="muted mt-2 text-xs">WA Admin: nomor diawali 62 tanpa + atau spasi. Sosmed dan Saluran: tautan lengkap diawali https://.</p></div>
        <div><label class="label" for="new-subtitle">Keterangan (opsional)</label><input class="field" id="new-subtitle" name="subtitle" value="{{ old('subtitle') }}" maxlength="120" placeholder="Contoh: Khusus jual beli akun FF"></div>
        <div><label class="label" for="new-badge">Label (opsional)</label><input class="field" id="new-badge" name="badge" value="{{ old('badge') }}" maxlength="12" placeholder="Otomatis: WA, IG, TIKTOK, ..."></div>
        <div><label class="label" for="new-order">Urutan</label><input class="field" id="new-order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', 0) }}"></div>
        <div class="md:col-span-2"><button class="btn w-full sm:w-auto">+ Tambah kontak</button></div>
    </form>

    @foreach (\App\Models\ContactLink::GROUPS as $group => $groupLabel)
        <h2 class="mb-3 mt-8 text-lg font-bold">{{ $groupLabel }} <span class="muted text-sm font-normal">({{ count($contacts[$group] ?? []) }})</span></h2>
        <div class="space-y-3">
            @forelse ($contacts[$group] ?? [] as $contact)
                <details class="panel p-4 md:p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ $contact->title }}</span><span class="rounded-full bg-lime-300/10 px-2 py-0.5 text-[10px] font-bold text-lime-300">{{ $contact->badgeLabel() }}</span></span>
                            <span class="muted block break-all text-xs">{{ $contact->value }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-lime-300">Ubah</span>
                    </summary>
                    <form action="{{ route('admin.contacts.update', $contact) }}" method="POST" class="mt-4 grid gap-4 border-t border-white/10 pt-4 md:grid-cols-2">
                        @csrf @method('PUT')
                        <div><label class="label" for="group-{{ $contact->id }}">Jenis</label><select class="field" id="group-{{ $contact->id }}" name="group" required>
                                @foreach (\App\Models\ContactLink::GROUPS as $value => $label)
                                    <option value="{{ $value }}" @selected($contact->group === $value)>{{ $label }}</option>
                                @endforeach
                            </select></div>
                        <div><label class="label" for="title-{{ $contact->id }}">Nama</label><input class="field" id="title-{{ $contact->id }}" name="title" value="{{ $contact->title }}" maxlength="80" required></div>
                        <div><label class="label" for="value-{{ $contact->id }}">Nomor WhatsApp atau tautan</label><input class="field" id="value-{{ $contact->id }}" name="value" value="{{ $contact->value }}" maxlength="500" required></div>
                        <div><label class="label" for="subtitle-{{ $contact->id }}">Keterangan</label><input class="field" id="subtitle-{{ $contact->id }}" name="subtitle" value="{{ $contact->subtitle }}" maxlength="120"></div>
                        <div><label class="label" for="badge-{{ $contact->id }}">Label</label><input class="field" id="badge-{{ $contact->id }}" name="badge" value="{{ $contact->badge }}" maxlength="12" placeholder="Otomatis"></div>
                        <div><label class="label" for="order-{{ $contact->id }}">Urutan</label><input class="field" id="order-{{ $contact->id }}" name="sort_order" type="number" min="0" max="999" value="{{ $contact->sort_order }}"></div>
                        <div class="md:col-span-2"><button class="btn-secondary w-full sm:w-auto">Simpan perubahan</button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" class="mt-3" data-confirm="Hapus kontak ini?">
                        @csrf @method('DELETE')
                        <button class="min-h-9 w-full rounded-lg border border-rose-300/20 px-4 text-xs text-rose-300 transition hover:bg-rose-300/10 sm:w-auto md:min-h-11 md:text-sm">Hapus kontak</button>
                    </form>
                </details>
            @empty
                <p class="panel p-6 text-center text-sm text-slate-400">Belum ada kontak {{ $groupLabel }}.</p>
            @endforelse
        </div>
    @endforeach
@endsection
