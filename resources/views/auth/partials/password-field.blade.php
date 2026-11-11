{{-- Password input with a show/hide eye button (handled by [data-password-toggle] in app.js). --}}
<div class="relative">
    <input id="{{ $id }}" name="{{ $name }}" type="password" class="field pr-12" @unless ($optional ?? false) required @endunless @if ($minlength ?? false) minlength="{{ $minlength }}" @endif autocomplete="{{ $autocomplete }}">
    <button type="button" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-white" data-password-toggle="{{ $id }}" aria-label="Tampilkan password" aria-pressed="false">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" /></svg>
    </button>
</div>
