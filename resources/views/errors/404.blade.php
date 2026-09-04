@extends('errors.layout')

@section('title', 'That page is not here')
@section('message')
    The address may be mistyped, or the thing it pointed at has been removed.
@endsection
@section('ways')
    <a href="{{ url('/') }}">Go to the start</a>
    @auth<a href="{{ url()->previous() }}">Back to the last page</a>@endauth
@endsection
@section('code', 'Error 404')
