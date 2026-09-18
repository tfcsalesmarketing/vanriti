@extends('admin.errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('icon', 'bi bi-speedometer2')
@section('message', "You're going a little too fast. Please wait a moment and try again.")

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
