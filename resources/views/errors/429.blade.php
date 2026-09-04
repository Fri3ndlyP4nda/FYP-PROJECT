@extends('errors.layout')

@section('title', 'Too many attempts')
@section('message')
    This has been tried several times in a short period, so it is paused for a few
    minutes. This is deliberate: it is what stops someone guessing their way into
    an account.
@endsection
@section('detail')
    Wait a few minutes and try again. If you have forgotten your password, reset it
    rather than guessing.
@endsection
@section('ways')
    <a href="{{ route('password.request') }}">Reset your password</a>
    <a href="{{ route('login') }}">Back to sign in</a>
@endsection
@section('code', 'Error 429')
