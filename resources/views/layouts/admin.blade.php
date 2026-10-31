@extends('layouts.app')
@section('content')
    <div class="shell py-6 md:py-10">
        <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
            <aside class="panel h-fit p-3 lg:sticky lg:top-24 lg:p-4">
                <div class="flex items-center justify-between gap-2 border-b border-white/10 pb-3">
                    <p class="eyebrow text-[10px] tracking-widest">Admin workspace</p>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-2 text-xs text-slate-300 transition hover:bg-white/5 hover:text-white">
                            Keluar
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M14 8l4 4-4 4M8 12h13" />
                            </svg>
                        </button>
                    </form>
                </div>
                <nav class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-1" aria-label="Navigasi admin">
                    @foreach (['dashboard' => 'Ringkasan', 'accounts' => 'Akun game', 'categories' => 'Kategori', 'settings' => 'Pengaturan WhatsApp'] as $route => $label)
                        <a href="{{ route('admin.' . $route) }}"
                            class="flex min-h-11 min-w-0 items-center gap-2 rounded-xl border px-2 py-2 text-xs transition hover:border-lime-300/50 hover:bg-[#a9c3ff]/10 lg:gap-3 lg:px-4 lg:py-3 lg:text-sm {{ request()->routeIs('admin.' . $route . '*') ? 'border-lime-300/40 bg-lime-300/10 text-lime-300' : 'border-[#a9c3ff]/15 bg-[#a9c3ff]/5 text-slate-400' }}"
                            @if (request()->routeIs('admin.' . $route . '*')) aria-current="page" @endif>
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg lg:h-9 lg:w-9 lg:border lg:border-current/15 lg:bg-current/5"
                                aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    @switch($route)
                                        @case('dashboard')
                                            <rect x="3" y="3" width="7" height="10" rx="1.5" />
                                            <rect x="14" y="3" width="7" height="6" rx="1.5" />
                                            <rect x="3" y="17" width="7" height="4" rx="1.5" />
                                            <rect x="14" y="13" width="7" height="8" rx="1.5" />
                                        @break

                                        @case('accounts')
                                            <path
                                                d="M7.5 7h9a4 4 0 0 1 3.9 3.1l1.1 6.1a2.5 2.5 0 0 1-4.2 2.2L14.5 16h-5l-2.8 2.4a2.5 2.5 0 0 1-4.2-2.2l1.1-6.1A4 4 0 0 1 7.5 7Z" />
                                            <path d="M7.5 10v5M5 12.5h5" />
                                            <circle cx="16" cy="11" r=".75" fill="currentColor" stroke="none" />
                                            <circle cx="18" cy="14" r=".75" fill="currentColor" stroke="none" />
                                        @break

                                        @case('categories')
                                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                        @break

                                        @case('settings')
                                            <path
                                                d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" />
                                            <path d="M8 10h9M8 14h6" />
                                        @break
                                    @endswitch
                                </svg>
                            </span>
                            @if ($route === 'settings')
                                <span class="lg:hidden">WhatsApp<span class="sr-only"> — pengaturan toko</span></span>
                                <span class="hidden lg:inline">{{ $label }}</span>
                            @else
                                <span>{{ $label }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>
            <section class="min-w-0">
                <div class="mb-7">
                    <h1 class="mt-2 text-3xl font-black tracking-tight md:text-4xl">@yield('admin-title', 'Dashboard admin')<span
                            class="text-lime-300">.</span></h1>
                </div>
                @yield('admin-content')
            </section>
        </div>
    </div>
@endsection
