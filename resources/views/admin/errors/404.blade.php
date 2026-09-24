@extends('admin.errors.layout')

@section('title', 'Page Not Found')
@section('code', '404')
@section('icon', 'bi bi-compass')
@section('message', "The admin page you're looking for doesn't exist or may have been moved.")

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
