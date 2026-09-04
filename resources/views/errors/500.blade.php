@extends('errors.layout')

@section('title', 'Something broke at our end')
@section('message')
    This one is not your fault. The fault has been recorded and nothing you
    submitted was lost.
@endsection
@section('detail')
    If it keeps happening, tell the faculty office what you were doing when it
    started &mdash; that is what makes it findable.
@endsection
@section('ways')
    <a href="{{ url('/') }}">Go to the start</a>
@endsection
@section('code', 'Error 500')
