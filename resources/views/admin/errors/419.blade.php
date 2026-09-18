@extends('admin.errors.layout')

@section('title', 'Page Expired')
@section('code', '419')
@section('icon', 'bi bi-hourglass-split')
@section('message', 'Your admin session has expired. Please sign in again.')

@section('buttons')
    <a href="{{ route('admin.login') }}" class="ad-btn-back"><i class="bi bi-box-arrow-in-right"></i> Sign in to Admin</a>
@endsection
