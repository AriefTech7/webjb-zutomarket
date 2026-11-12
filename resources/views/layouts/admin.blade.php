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
                    @php($unreadMessages = \App\Models\FeedbackMessage::whereNull('read_at')->count())
                    @php($editedAccount = request()->route('account'))
                    @php($inRentalForm = request()->routeIs('admin.accounts.*') && ($editedAccount instanceof \App\Models\GameAccount ? $editedAccount->is_rental : request()->boolean('rental')))
                    @foreach (['dashboard' => 'Ringkasan', 'accounts' => 'Akun game', 'rentals' => 'Rental akun', 'sales' => 'Data penjualan', 'messages' => 'Pesan masuk', 'buyers' => 'Akun pembeli', 'admins' => 'Akun admin', 'categories' => 'Kategori', 'infos' => 'Info terbaru', 'contacts' => 'Kontak & Sosmed', 'settings' => 'Pengaturan WhatsApp'] as $route => $label)
                        @php($isActiveMenu = $route === 'rentals' ? request()->routeIs('admin.rentals*') || $inRentalForm : request()->routeIs('admin.' . $route . '*') && ! ($route === 'accounts' && $inRentalForm))
                        <a href="{{ route('admin.' . $route) }}"
                            class="flex min-h-11 min-w-0 items-center gap-2 rounded-xl border px-2 py-2 text-xs transition hover:border-lime-300/50 hover:bg-[#a9c3ff]/10 lg:gap-3 lg:px-4 lg:py-3 lg:text-sm {{ $isActiveMenu ? 'border-lime-300/40 bg-lime-300/10 text-lime-300' : 'border-[#a9c3ff]/15 bg-[#a9c3ff]/5 text-slate-400' }}"
                            @if ($isActiveMenu) aria-current="page" @endif>
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

                                        @case('rentals')
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M12 7v5l3 2" />
                                        @break

                                        @case('sales')
                                            <path d="M3 3v18h18" />
                                            <path d="m7 15 4-4 3 3 5-6" />
                                        @break

                                        @case('messages')
                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                            <path d="m3 7 9 6 9-6" />
                                        @break

                                        @case('admins')
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                                            <circle cx="12" cy="10" r="2.5" />
                                            <path d="M8.5 16a3.5 3.5 0 0 1 7 0" />
                                        @break

                                        @case('buyers')
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="m16 11 2 2 4-4" />
                                        @break

                                        @case('infos')
                                            <path d="M3 11v3a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z" />
                                            <path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13" />
                                        @break

                                        @case('contacts')
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
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
                            @if ($route === 'messages' && $unreadMessages)
                                <span class="ml-auto rounded-full bg-lime-300 px-2 py-0.5 text-[10px] font-black text-slate-950" aria-label="{{ $unreadMessages }} pesan belum dibaca">{{ $unreadMessages }}</span>
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
