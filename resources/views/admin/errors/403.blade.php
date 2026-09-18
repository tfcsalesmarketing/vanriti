@extends('admin.errors.layout')

@section('title', 'Forbidden')
@section('code', '403')
@section('icon', 'bi bi-shield-x')
@section('message', "You don't have permission to access this admin page.")

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
