<ul class="space-y-3 md:hidden" aria-label="Daftar akun game">
    @forelse ($accounts as $account)
        <li class="panel min-w-0 p-4">
            <div class="flex items-start gap-3">
                <img class="h-14 w-16 shrink-0 rounded-lg object-cover" src="{{ $account->imageUrl() }}" alt="" loading="lazy">
                <div class="min-w-0 flex-1">
                    <p class="break-words text-sm font-semibold">{{ $account->title }}</p>
                    <p class="muted mt-1 break-words text-xs">{{ $account->category->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $account->created_at->format('d M Y') }}</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-white/10 pt-3">
                <p class="text-sm font-bold text-lime-300">{{ $account->formattedPrice() }}</p>
                <span class="status {{ ['available' => 'text-lime-300', 'sold' => 'text-rose-300', 'draft' => 'text-slate-400'][$account->status] }}">{{ ['available' => 'Tersedia', 'sold' => 'Terjual', 'draft' => 'Draft'][$account->status] }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <a class="btn-secondary px-3" href="{{ route('admin.accounts.edit', $account) }}" aria-label="Edit {{ $account->title }}">Edit akun</a>
                <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" data-confirm="Hapus akun ini beserta gambarnya?">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-rose-300/20 bg-rose-300/5 px-3 py-3 text-sm font-semibold text-rose-300 transition hover:bg-rose-300/10" aria-label="Hapus {{ $account->title }}">Hapus</button>
                </form>
            </div>
        </li>
    @empty
        <li class="panel p-6 text-center text-sm text-slate-400">Belum ada akun. Tambahkan akun pertamamu.</li>
    @endforelse
</ul>
<div class="panel hidden overflow-x-auto md:block">
    <table class="w-full min-w-[650px] text-left text-sm">
        <thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-500">
            <tr>
                <th class="p-5">Akun game</th>
                <th class="p-5">Harga</th>
                <th class="p-5">Status</th>
                <th class="p-5">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($accounts as $account)
                <tr class="border-b border-white/5">
                    <td class="p-5">
                        <div class="flex items-center gap-3"><img class="h-12 w-16 rounded-lg object-cover"
                                src="{{ $account->imageUrl() }}" alt="">
                            <div>
                                <p class="font-semibold">{{ $account->title }}</p>
                                <p class="muted text-xs">{{ $account->category->name }} ·
                                    {{ $account->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="p-5 whitespace-nowrap">{{ $account->formattedPrice() }}</td>
                    <td class="p-5"><span
                            class="status {{ ['available' => 'text-lime-300', 'sold' => 'text-rose-300', 'draft' => 'text-slate-400'][$account->status] }}">{{ ['available' => 'Tersedia', 'sold' => 'Terjual', 'draft' => 'Draft'][$account->status] }}</span>
                    </td>
                    <td class="p-5">
                        <div class="flex items-center gap-4"><a class="py-3 text-lime-300"
                                href="{{ route('admin.accounts.edit', $account) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}"
                                data-confirm="Hapus akun ini beserta gambarnya?">@csrf @method('DELETE')<button
                                    class="py-3 text-rose-300">Hapus</button></form>
                        </div>
                    </td>
            </tr>@empty<tr>
                    <td colspan="4" class="p-10 text-center text-slate-400">Belum ada akun. Tambahkan akun pertamamu.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
