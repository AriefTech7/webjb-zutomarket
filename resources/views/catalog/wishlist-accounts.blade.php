@foreach($accounts as $account)
    @include('catalog.account-card', ['account' => $account])
@endforeach
