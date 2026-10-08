@extends('storefront.layouts.app')
@section('title', 'Checkout')
@section('robots', 'noindex, nofollow')

@section('content')
@include('storefront.checkout.form')
@endsection
