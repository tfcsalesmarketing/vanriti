@extends('errors.layout')

@section('title', 'Page Expired')
@section('code', '419')
@section('icon', 'bi bi-hourglass-split')
@section('message', 'Your session has expired. Please try again.')

@section('buttons')
    <a href="#" class="btn btn-vr-outline" onclick="history.back(); return false;">Try again</a>
    <a href="{{ route('home') }}" class="btn btn-vr">Go back home</a>
@endsection