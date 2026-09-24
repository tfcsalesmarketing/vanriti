@extends('admin.errors.layout')

@section('title', 'Server Error')
@section('code', '500')
@section('icon', 'bi bi-tools')
@section('message', 'An unexpected error occurred on the admin panel. Please try again in a few minutes.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
