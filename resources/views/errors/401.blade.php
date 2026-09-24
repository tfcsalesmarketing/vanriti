@extends('errors.layout')

@section('title', 'Unauthorized')
@section('code', '401')
@section('icon', 'bi bi-shield-lock')
@section('message', 'You are not authorized to view this page. If you believe this is a mistake, please sign in and try again.')