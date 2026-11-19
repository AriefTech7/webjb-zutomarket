@extends('layouts.app')
@section('content')
    @php($storeName = \App\Models\Setting::valueFor('store_name', 'SHOPGAME ZUTO'))
    @php($unreadMessages = \App\Models\FeedbackMessage::whereNull('read_at')->count())
    @php($adminUser = auth()->user())
    <div class="shell max-w-full py-4 md:py-6">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-6">
            {{-- Desktop sidebar. On phones the same menu lives behind the "⋮" button in the top bar. --}}
            <aside class="panel sticky top-6 hidden min-h-[calc(100vh-3rem)] flex-col p-5 lg:flex">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-1" aria-label="{{ $storeName }} — ringkasan admin">
                    <span class="logo-shine" style="--logo-url: url('{{ asset('images/zuto-wordmark.webp') }}')"><img src="{{ asset('images/zuto-wordmark.webp') }}"
                            alt="{{ $storeName }}" width="1199" height="422" class="brand-wordmark w-36"></span>
                </a>
                <p class="eyebrow mb-3 mt-5 px-1 text-[10px] tracking-widest">Admin workspace</p>
                @include('layouts.admin-nav', ['navLabel' => 'Navigasi admin'])
                <div class="joki-banner relative mt-auto overflow-hidden rounded-2xl p-5 text-center text-[#fff]">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-[#fff]/20" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M3 9h18l-1.5-5h-15L3 9Zm0 0v11h18V9M9 20v-6h6v6" />
                        </svg>
                    </span>
                    <p class="mt-3 font-black">Lihat toko</p>
                    <p class="mt-1 text-xs text-[#fff]/80">Cek tampilan {{ $storeName }} seperti yang dilihat pembeli.</p>
                    <a href="{{ route('catalog') }}" target="_blank" rel="noopener"
                        class="mt-4 inline-flex min-h-10 items-center justify-center rounded-xl bg-[#fff] px-5 text-sm font-black text-[#3b2a8f] transition hover:bg-[#fff]/90">Buka toko</a>
                </div>
            </aside>
            <section class="min-w-0">
                <div class="panel sticky top-2 z-40 mb-6 flex flex-wrap items-center gap-3 p-3 md:px-5 lg:static lg:z-auto lg:flex-nowrap">
                    <a href="{{ route('admin.dashboard') }}" class="shrink-0 lg:hidden" aria-label="{{ $storeName }} — ringkasan admin">
                        <img src="{{ asset('images/zuto-wordmark.webp') }}" alt="{{ $storeName }}" width="1199" height="422" class="brand-wordmark w-24 sm:w-28">
                    </a>
                    <form action="{{ route('admin.accounts') }}" method="GET" role="search" class="relative order-last w-full lg:order-none lg:max-w-md">
                        <label class="sr-only" for="admin-search">Cari akun game</label>
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                        <input id="admin-search" type="search" name="q" value="{{ request()->routeIs('admin.accounts') ? request('q') : '' }}" maxlength="100"
                            placeholder="Cari akun game..." class="field rounded-full py-2.5 pl-11">
                    </form>
                    <div class="ml-auto flex items-center gap-2 md:gap-3">
                        <a href="{{ route('admin.messages') }}" class="theme-toggle relative" aria-label="Pesan masuk{{ $unreadMessages ? ', '.$unreadMessages.' belum dibaca' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0" /></svg>
                            @if ($unreadMessages)
                                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-400 px-1 text-[10px] font-black text-slate-950" aria-hidden="true">{{ $unreadMessages }}</span>
                            @endif
                        </a>
                        <button type="button" class="theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Ganti ke mode terang">
                            <svg class="theme-icon-sun h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></svg>
                            <svg class="theme-icon-moon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" /></svg>
                        </button>
                        <div class="flex items-center gap-3 lg:border-l lg:border-white/10 lg:pl-3">
                            <span class="joki-banner flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-black text-[#fff]" title="{{ $adminUser->name }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($adminUser->name, 0, 1)) }}</span>
                            <span class="hidden min-w-0 leading-tight lg:block">
                                <span class="block max-w-40 truncate text-sm font-bold text-white">{{ $adminUser->name }}</span>
                                <span class="block text-xs text-slate-400">{{ '@'.$adminUser->username }}</span>
                            </span>
                        </div>
                        <details class="relative lg:hidden" data-admin-menu>
                            <summary class="theme-toggle list-none [&::-webkit-details-marker]:hidden" aria-label="Menu admin">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="12" cy="5" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="12" cy="19" r="2" /></svg>
                            </summary>
                            <div class="absolute right-0 top-full z-50 mt-3 max-h-[75vh] w-72 max-w-[calc(100vw-2.5rem)] overflow-y-auto rounded-2xl border border-white/10 bg-[#101833] p-2 shadow-2xl shadow-[#020617]/60">
                                <div class="mb-1 flex items-center gap-3 border-b border-white/10 px-3 pb-3 pt-2">
                                    <span class="min-w-0 leading-tight">
                                        <span class="block truncate text-sm font-bold text-white">{{ $adminUser->name }}</span>
                                        <span class="block text-xs text-slate-400">{{ '@'.$adminUser->username }}</span>
                                    </span>
                                    <a href="{{ route('catalog') }}" target="_blank" rel="noopener" class="ml-auto shrink-0 rounded-full bg-lime-300/15 px-3 py-1 text-xs font-bold text-lime-300">Buka toko</a>
                                </div>
                                @include('layouts.admin-nav', ['navLabel' => 'Menu admin'])
                            </div>
                        </details>
                    </div>
                </div>
                @include('layouts.flash', ['flashClass' => 'mb-5'])
                <div class="mb-6">
                    <p class="eyebrow mb-2 text-[10px] tracking-widest lg:hidden">Admin workspace</p>
                    <h1 class="text-3xl font-black tracking-tight md:text-4xl">@yield('admin-title', 'Dashboard admin')<span
                            class="text-lime-300">.</span></h1>
                </div>
                @yield('admin-content')
            </section>
        </div>
    </div>
@endsection
