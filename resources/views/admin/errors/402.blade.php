@extends('admin.errors.layout')

@section('title', 'Payment Required')
@section('code', '402')
@section('icon', 'bi bi-credit-card')
@section('message', 'Payment is required to continue.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
