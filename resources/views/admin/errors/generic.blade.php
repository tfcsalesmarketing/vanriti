@extends('admin.errors.layout')

@section('title', 'Something Went Wrong')
@section('code', 'Error')
@section('icon', 'bi bi-exclamation-triangle')
@section('message', 'An unexpected error occurred in the admin panel. Please try again.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
