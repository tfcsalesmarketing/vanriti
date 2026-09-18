@extends('admin.errors.layout')

@section('title', 'Bad Request')
@section('code', '400')
@section('icon', 'bi bi-slash-circle')
@section('message', 'The admin request was malformed or could not be understood.')

@section('buttons')
    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
@endsection
