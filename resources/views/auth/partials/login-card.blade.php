{{-- Buyer sign-in card, shown on /masuk and on /profil for guests. --}}
@php($google = \App\Http\Controllers\GoogleAuthController::enabled())
<div class="panel mx-auto max-w-md p-6 sm:p-8">
    <h1 class="text-2xl font-black md:text-3xl">Masuk ke Akunmu</h1>
    <p class="muted mt-1 text-sm">Belum punya akun? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.register') }}">Daftar</a></p>
    <p class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4 text-sm leading-6 text-slate-300">Masuk untuk menyimpan wishlist dan profil pemesananmu, supaya tetap ada di HP maupun komputer.</p>
    @include('auth.partials.divider', ['text' => $google ? 'Atau gunakan email' : 'Masuk dengan email'])
    <form method="POST" action="{{ route('account.login') }}" class="space-y-5">
        @csrf
        <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></div>
        <div>
            <div class="flex items-center justify-between gap-3"><label class="label" for="password">Password</label><a class="mb-2 text-xs font-bold text-lime-300 hover:underline" href="{{ route('password.request') }}">Lupa password?</a></div>
            @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'current-password'])
        </div>
        <button class="btn w-full rounded-full">Masuk</button>
    </form>
    @include('auth.partials.google', ['divider' => 'Atau masuk dengan Google', 'label' => 'Masuk dengan Google'])
</div>
