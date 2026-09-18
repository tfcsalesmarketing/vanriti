@extends('admin.errors.layout')

@section('title', 'Method Not Allowed')
@section('code', '405')
@section('icon', 'bi bi-sign-stop')
@section('message', 'This HTTP method is not allowed for the admin endpoint you requested.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
