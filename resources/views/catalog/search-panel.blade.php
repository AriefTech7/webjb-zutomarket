<div class="mb-6 hidden flex-wrap items-end justify-between gap-x-4 gap-y-1 md:flex">
    <div>
        <h2 class="text-2xl font-bold tracking-tight md:text-3xl">Jelajahi katalog<span
                class="text-lime-300">.</span></h2>
    </div><span class="muted text-xs md:text-sm">{{ $total }} akun ditemukan</span>
</div>
<form action="{{ route('catalog.all') }}#katalog" method="GET" class="panel mb-5 hidden p-4 md:block">
    <div class="grid gap-3 md:grid-cols-[1fr_190px_180px_auto]">
        <div><label class="sr-only" for="search">Cari akun game</label><input id="search" class="field"
                type="search" name="q" value="{{ request('q') }}" maxlength="100"
                placeholder="Cari game, rank, hero, skin, atau server..."></div>
        <div><label class="sr-only" for="category">Kategori game</label><select class="field" id="category"
                name="category">
                <option value="">Semua game</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($selected === $category->slug)>{{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div><label class="sr-only" for="sort">Urutkan</label><select class="field" id="sort"
                name="sort">
                <option value="newest">Paling baru</option>
                <option value="price_low" @selected(request('sort') === 'price_low')>Harga terendah</option>
                <option value="price_high" @selected(request('sort') === 'price_high')>Harga tertinggi</option>
            </select></div><button class="btn">Cari akun ↗</button>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-between gap-3"><label
            class="flex items-center gap-2 text-xs text-slate-400"><input type="checkbox"
                class="accent-lime-300" name="available" value="1" data-submit-on-change @checked(request('available'))> Hanya akun
            tersedia</label>
        @if (request()->query() || $selected)
            <a class="text-xs text-lime-300" href="{{ route('catalog.all') }}#katalog">Reset pencarian ×</a>
        @endif
    </div>
</form>
