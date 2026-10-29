@extends('layouts.app')
@section('content')
<div class="shell py-6 md:py-10">
    <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="panel h-fit p-4 lg:sticky lg:top-24">
            <div class="border-b border-white/10 px-2 pb-5">
                <p class="eyebrow">Admin workspace</p>
                <p class="mt-2 truncate font-bold">{{ auth()->user()->name }}</p>
                <p class="muted truncate text-xs">@{{ auth()->user()->username }}</p>
            </div>
            <nav class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1" aria-label="Navigasi admin">
                @foreach(['dashboard' => ['Ringkasan', '⌂'], 'accounts' => ['Akun game', '◫'], 'categories' => ['Kategori', '◇'], 'settings' => ['Pengaturan WhatsApp', '⌁']] as $route => [$label, $icon])
                    <a href="{{ route('admin.'.$route) }}" class="admin-link flex items-center gap-3 {{ request()->routeIs('admin.'.$route.'*') ? 'border-lime-300/40 bg-lime-300/10 text-lime-300' : 'text-slate-400' }}"><span aria-hidden="true">{{ $icon }}</span><span>{{ $label }}</span></a>
                @endforeach
            </nav>
            <form class="mt-4 border-t border-white/10 pt-4" method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn-secondary w-full">Keluar <span aria-hidden="true">↗</span></button></form>
        </aside>
        <section class="min-w-0">
            <div class="mb-7">
                <p class="eyebrow">Kelola toko</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">@yield('admin-title', 'Dashboard admin')<span class="text-lime-300">.</span></h1>
            </div>
            @yield('admin-content')
        </section>
    </div>
</div>
@endsection
