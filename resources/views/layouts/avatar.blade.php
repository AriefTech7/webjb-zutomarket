{{-- An admin's profile photo, or their initial on a coloured circle when they have none. Pass $user and $class (size etc.). --}}
@if ($url = $user->avatarUrl())
    <img src="{{ $url }}" alt="{{ $alt ?? '' }}" title="{{ $user->name }}" class="{{ $class }} object-cover" loading="lazy">
@else
    <span class="{{ $class }} flex items-center justify-center font-black text-white" title="{{ $user->name }}" style="background: hsl({{ ($user->id * 67) % 360 }} 70% 50%)">{{ $user->initial() }}</span>
@endif
