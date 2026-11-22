@if (session('success'))
    <div role="status" class="{{ $flashClass }}">
        <div class="rounded-xl border border-lime-300/30 bg-lime-300/10 p-4 text-sm text-lime-200">
            {{ session('success') }}</div>
    </div>
@endif
@if (session('error'))
    <div role="alert" class="{{ $flashClass }}">
        <div class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-200">{{ session('error') }}</div>
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="{{ $flashClass }}">
        <ul class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-200">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
