@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
    <section class="shell py-8 md:py-14">
        @include('auth.partials.login-card')
    </section>
@endsection
