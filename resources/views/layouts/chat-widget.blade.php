{{--
    Live chat (bottom right, above the phone navigation). The header shows how many chat admins are online with their
    initials. Signed-in buyers chat in the panel; guests see a greeting with a sign-in button, because only buyers with
    an account can chat. Hidden for admins (they answer in Admin > Live chat).
--}}
@php($chatUser = auth()->user())
@if ($chatUser?->is_super_admin)
    {{-- The super admin answers chats in the admin area: the launcher goes there and shows how many are waiting. --}}
    @php($waitingChats = \App\Models\ChatConversation::unreadByAdmin()->count())
    <a href="{{ route('admin.chat') }}" class="chat-launcher" aria-label="Buka Live chat admin{{ $waitingChats ? ', '.$waitingChats.' belum dibalas' : '' }}" title="Live chat admin">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z" /></svg>
        @if ($waitingChats)<span class="chat-badge">{{ $waitingChats > 9 ? '9+' : $waitingChats }}</span>@endif
    </a>
@endif
@unless ($chatUser?->is_admin)
    @php($onlineAdmins = \App\Models\User::onlineChatAdmins())
    @php($chatIcon = 'M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3H13a8.5 8.5 0 0 1 8 8v.5Z')
    <div data-chat-widget>
        <button type="button" class="chat-launcher" data-chat-toggle data-chat-launcher aria-expanded="false" aria-controls="chat-panel" aria-label="Live chat">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $chatIcon }}" /></svg>
            @if ($onlineAdmins->isNotEmpty())<span class="chat-online-dot" aria-hidden="true"></span>@endif
            <span class="chat-badge" data-chat-badge hidden>0</span>
        </button>
        <section id="chat-panel" class="chat-panel" data-chat-panel hidden aria-labelledby="chat-title">
            <header class="flex items-center gap-3 border-b border-white/10 px-4 py-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full border border-lime-300/40 text-lime-300" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="{{ $chatIcon }}" /></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="chat-title" class="text-lg font-black leading-tight">Live Chat</h2>
                    @if ($onlineAdmins->isNotEmpty())
                        <p class="flex items-center gap-1.5 truncate text-xs font-semibold text-emerald-400"><span class="chat-pulse" aria-hidden="true"></span>{{ $onlineAdmins->count() }} Admin Online</p>
                    @else
                        <p class="truncate text-xs text-slate-400">Admin sedang offline · pesanmu tetap dibalas</p>
                    @endif
                </div>
                @if ($onlineAdmins->isNotEmpty())
                    <div class="hidden shrink-0 -space-x-2 min-[360px]:flex" aria-hidden="true">
                        @foreach ($onlineAdmins->take(3) as $admin)
                            @include('layouts.avatar', ['user' => $admin, 'class' => 'chat-avatar', 'alt' => $admin->name])
                        @endforeach
                        @if ($onlineAdmins->count() > 3)
                            <span class="chat-avatar chat-avatar-more">+{{ $onlineAdmins->count() - 3 }}</span>
                        @endif
                    </div>
                @endif
                <button type="button" data-chat-expand aria-pressed="false" class="flex size-9 shrink-0 items-center justify-center rounded-full text-slate-300 transition hover:bg-white/10 hover:text-white" aria-label="Perbesar chat">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" /></svg>
                </button>
                <button type="button" data-chat-toggle class="flex size-9 shrink-0 items-center justify-center rounded-full text-slate-300 transition hover:bg-white/10 hover:text-white" aria-label="Tutup chat">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </header>

            @if ($chatUser)
                <div class="flex min-h-0 flex-1 flex-col" data-chat data-chat-endpoint="{{ route('chat.messages') }}" data-chat-send="{{ route('chat.send') }}">
                    <div class="m-auto max-w-64 p-6 text-center" data-chat-empty>
                        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-lime-300/10 text-lime-300" aria-hidden="true"><svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="{{ $chatIcon }}" /></svg></span>
                        <p class="mt-4 font-black">Halo {{ $chatUser->name }}!</p>
                        <p class="mt-1 text-sm text-slate-400">Tulis pertanyaanmu tentang akun, rental, joki, atau pembayaran.</p>
                    </div>
                    <ul class="chat-list" data-chat-list aria-live="polite"></ul>
                    @include('layouts.chat-form')
                </div>
            @else
                <div class="m-auto max-w-72 p-6 text-center">
                    <span class="mx-auto flex size-20 items-center justify-center rounded-full bg-lime-300/10 text-lime-300" aria-hidden="true"><svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="{{ $chatIcon }}" /></svg></span>
                    <p class="mt-5 text-lg font-black">Halo!</p>
                    <p class="mt-1 text-sm text-slate-400">Silakan masuk ke akunmu untuk mulai mengobrol dengan tim kami.</p>
                    <a href="{{ route('account.login') }}" class="btn mt-6 w-full rounded-full">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" /></svg>
                        Login untuk Mulai Chat
                    </a>
                    <p class="mt-3 text-xs text-slate-500">Belum punya akun? <a class="font-semibold text-lime-300 hover:underline" href="{{ route('account.register') }}">Daftar gratis</a></p>
                </div>
            @endif
        </section>
    </div>
@endunless
