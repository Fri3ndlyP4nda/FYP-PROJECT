@extends('errors.layout')

@section('title', 'This page sat open too long')
@section('message')
    For safety, a form stops being accepted after a while. Nothing was saved and
    nothing was lost &mdash; open the page again and resubmit.
@endsection
@section('detail')
    If you were part-way through an application, your saved draft is untouched.
@endsection
@section('ways')
    <a href="{{ route('login') }}">Sign in again</a>
@endsection
@section('code', 'Error 419')
