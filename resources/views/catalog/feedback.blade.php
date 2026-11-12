@extends('layouts.app')
@section('title', 'Kritik & saran')
@section('description', 'Kirim kritik dan saran untuk ZutoMarket langsung ke admin.')
@section('content')
    @php($buyer = auth()->user()?->is_admin === false ? auth()->user() : null)
    <section class="shell max-w-2xl py-10">
        <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-slate-300 transition hover:text-white" href="{{ route('profile') }}">← Kembali</a>
        <p class="eyebrow mt-4">Akun saya</p>
        <h1 class="mt-3 text-4xl font-black">Kritik &amp; saran<span class="text-lime-300">.</span></h1>
        <p class="muted mt-4">Masukanmu membantu kami melayani lebih baik. Pesan langsung masuk ke admin ZutoMarket.</p>
        <form method="POST" action="{{ route('feedback.store') }}" class="panel mt-8 space-y-5 p-6">
            @csrf
            <div class="hidden" aria-hidden="true"><label for="feedback-website">Website</label><input id="feedback-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div><label class="label" for="feedback-type">Jenis</label><select id="feedback-type" class="field" name="type" required>
                    @foreach (\App\Models\FeedbackMessage::TYPES as $type)
                        <option @selected(old('type') === $type)>{{ $type }}</option>
                    @endforeach
                </select></div>
            <div><label class="label" for="feedback-name">Nama (opsional)</label><input id="feedback-name" class="field" type="text" name="name" value="{{ old('name', $buyer?->name) }}" maxlength="50" autocomplete="name" placeholder="Nama kamu"></div>
            <div><label class="label" for="feedback-contact">No. WhatsApp atau email (opsional)</label><input id="feedback-contact" class="field" type="text" name="contact" value="{{ old('contact', $buyer?->whatsapp ?: $buyer?->email) }}" maxlength="100" placeholder="Supaya admin bisa membalas">
                <p class="muted mt-2 text-xs">Isi kalau ingin dibalas admin.</p></div>
            <div><label class="label" for="feedback-message">Pesan</label><textarea id="feedback-message" class="field" name="message" rows="6" minlength="5" maxlength="1500" required placeholder="Tulis kritik atau saranmu di sini">{{ old('message') }}</textarea></div>
            <button class="btn w-full sm:w-auto">Kirim ke admin</button>
        </form>
    </section>
@endsection
