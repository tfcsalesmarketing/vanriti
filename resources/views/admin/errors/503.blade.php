@extends('admin.errors.layout')

@section('title', 'Service Unavailable')
@section('code', '503')
@section('icon', 'bi bi-tools')
@section('message', 'The admin panel is temporarily down for maintenance. Please check back shortly.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
