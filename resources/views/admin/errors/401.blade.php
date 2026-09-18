@extends('admin.errors.layout')

@section('title', 'Unauthorized')
@section('code', '401')
@section('icon', 'bi bi-shield-lock')
@section('message', 'You are not authorized to access this admin panel area.')

@section('buttons')
    <a href="{{ route('admin.login') }}" class="ad-btn-back"><i class="bi bi-box-arrow-in-right"></i> Sign in to Admin</a>
@endsection
