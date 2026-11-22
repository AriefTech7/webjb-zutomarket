{{-- "Kirim laporan baru" dialog on /laporan. Opens again by itself when the server sent the form back with errors. --}}
@php($reportCategory = old('category', 'pengguna'))
<dialog class="info-viewer" data-report-dialog aria-labelledby="report-form-title" @if ($errors->any()) data-open-on-load @endif>
    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="info-viewer-box report-dialog-box relative space-y-4 rounded-3xl border border-white/10 bg-[#101833] p-5 shadow-2xl shadow-[#020617]/60 md:p-7" data-report-form>
        @csrf
        <button type="button" data-report-close class="absolute right-4 top-4 flex size-10 items-center justify-center rounded-full border border-white/15 bg-[#070b1c]/70 text-white transition hover:bg-rose-400 hover:text-slate-950" aria-label="Tutup">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" /></svg>
        </button>
        <p class="mr-12 flex items-center justify-center gap-2 rounded-full border border-lime-300/30 bg-lime-300/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-lime-300">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10ZM9 12l2 2 4-4" /></svg>
            Fitur pelapor
        </p>
        <div>
            <h2 id="report-form-title" class="text-2xl font-black">Kirim laporan baru<span class="text-lime-300">.</span></h2>
            <p class="muted mt-1">Email dan nomor WhatsApp di bawah adalah data pelapor untuk kebutuhan verifikasi admin. Data ini tidak ditampilkan ke publik.</p>
        </div>
        @if ($errors->any())
            <ul class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-3 text-sm text-rose-200">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label text-xs uppercase tracking-wide" for="reporter_name">Nama (panggilan)</label><input class="field" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}" maxlength="60" required placeholder="Contoh: Zuto"></div>
            <div><label class="label text-xs uppercase tracking-wide" for="reporter_email">Email pelapor</label><input class="field" id="reporter_email" name="reporter_email" type="email" value="{{ old('reporter_email') }}" maxlength="120" required placeholder="nama@email.com"></div>
            <div><label class="label text-xs uppercase tracking-wide" for="reporter_whatsapp">Nomor WhatsApp pelapor</label><input class="field" id="reporter_whatsapp" name="reporter_whatsapp" type="tel" inputmode="tel" value="{{ old('reporter_whatsapp') }}" maxlength="20" required placeholder="628xxxxxxxxxx"></div>
        </div>

        <fieldset>
            <legend class="label text-xs uppercase tracking-wide">Kategori</legend>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Models\Report::CATEGORIES as $categoryValue => $categoryLabel)
                    <label class="report-pill flex min-h-11 cursor-pointer items-center justify-center rounded-full border px-3 text-center text-sm font-bold transition">
                        <input class="sr-only" type="radio" name="category" value="{{ $categoryValue }}" @checked($reportCategory === $categoryValue)>{{ $categoryLabel }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div data-report-game-id @if ($reportCategory !== 'id_game') hidden @endif><label class="label text-xs uppercase tracking-wide" for="report_game_id">ID game bermasalah</label><input class="field" id="report_game_id" name="game_id" value="{{ old('game_id') }}" maxlength="50" placeholder="Contoh: 123456789" @disabled($reportCategory !== 'id_game') @required($reportCategory === 'id_game')></div>
            <div><label class="label text-xs uppercase tracking-wide" for="report_wa_number">No WA pelaku <span class="font-normal normal-case text-slate-500" data-report-wa-optional @if ($reportCategory !== 'id_game') hidden @endif>(opsional)</span></label><input class="field" id="report_wa_number" name="wa_number" type="tel" inputmode="tel" value="{{ old('wa_number') }}" maxlength="20" placeholder="628xxxxxxxxxx" @required($reportCategory === 'pengguna')></div>
        </div>
        <div><label class="label text-xs uppercase tracking-wide" for="report_title">Judul laporan</label><input class="field" id="report_title" name="title" value="{{ old('title') }}" maxlength="120" required placeholder="Ringkasan singkat laporan"></div>
        <div><label class="label text-xs uppercase tracking-wide" for="report_description">Detail laporan</label><textarea class="field" id="report_description" name="description" rows="5" minlength="20" maxlength="3000" required placeholder="Jelaskan kronologi, pihak terkait, nominal jika ada, dan bukti pendukung.">{{ old('description') }}</textarea></div>
        <div>
            <label class="label text-xs uppercase tracking-wide" for="report_evidence">Bukti upload image (1–{{ \App\Models\Report::MAX_EVIDENCE }} gambar)</label>
            <div class="rounded-2xl border border-white/10 bg-[#080f25] p-4">
                <input id="report_evidence" name="evidence[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required data-report-evidence
                    class="block w-full text-sm text-slate-300 file:mr-3 file:cursor-pointer file:rounded-full file:border-0 file:bg-lime-300 file:px-4 file:py-2 file:text-sm file:font-bold file:text-slate-950">
                <p class="muted mt-2 text-xs">Upload screenshot atau foto bukti. Maksimal {{ \App\Models\Report::MAX_EVIDENCE }} gambar, tiap gambar maksimal 10 MB (gambar besar dikompres otomatis).</p>
            </div>
        </div>
        <p class="flex gap-2 rounded-2xl border border-rose-300/25 bg-rose-300/5 p-4 text-sm text-slate-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-rose-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01" /></svg>
            Email dan WhatsApp pelapor hanya digunakan untuk verifikasi, permintaan bukti tambahan, proses banding/sengketa, dan komunikasi admin. Laporan tampil setelah dicek admin.
        </p>
        <button class="btn w-full rounded-full">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" /></svg>
            Kirim Laporan
        </button>
    </form>
</dialog>
