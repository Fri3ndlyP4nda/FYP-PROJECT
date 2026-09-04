@extends('errors.layout')

@section('title', 'That is not yours to open')
@section('message')
    You are signed in, but this belongs to someone else &mdash; another candidate's
    application, or a screen for a different role.
@endsection
@section('detail')
    If you believe you should have access, the faculty office can check what your
    account is set to.
@endsection
@section('ways')
    <a href="{{ url('/') }}">Go to your dashboard</a>
@endsection
@section('code', 'Error 403')
