{{-- Buyer sign-in card, shown on /masuk and on /profil for guests. --}}
@component('auth.partials.auth-card', ['title' => 'Masuk', 'googleLabel' => 'Masuk dengan Google'])
    <form method="POST" action="{{ route('account.login') }}" class="space-y-3">
        @csrf
        <label class="sr-only" for="email">Email</label>
        <input id="email" name="email" type="email" class="field min-h-12" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="Email">
        <label class="sr-only" for="password">Password</label>
        @include('auth.partials.password-field', ['id' => 'password', 'name' => 'password', 'autocomplete' => 'current-password', 'placeholder' => 'Password'])
        <button class="btn !mt-4 min-h-12 w-full">Masuk</button>
    </form>
    <p class="mt-4 text-center"><a class="text-sm font-bold text-lime-300 hover:underline" href="{{ route('password.request') }}">Lupa password?</a></p>
    <p class="muted mt-6 text-center">Belum punya akun? <a class="font-bold text-lime-300 hover:underline" href="{{ route('account.register') }}">Daftar</a></p>
@endcomponent
