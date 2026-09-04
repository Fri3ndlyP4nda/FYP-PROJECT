@extends('errors.layout')

@section('title', 'Down for maintenance')
@section('message')
    The system is being updated and will be back shortly. Nothing in progress has
    been lost.
@endsection
@section('ways')
    <a href="{{ url('/') }}">Try again</a>
@endsection
@section('code', 'Error 503')
