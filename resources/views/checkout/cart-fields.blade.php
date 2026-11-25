{{-- Hidden fields that say what is being bought: the game accounts (akun[]) or the joki brief in the session (joki=1). --}}
@foreach ($query['akun'] ?? [] as $accountId)
    <input type="hidden" name="akun[]" value="{{ $accountId }}">
@endforeach
@isset($query['joki'])
    <input type="hidden" name="joki" value="1">
@endisset
