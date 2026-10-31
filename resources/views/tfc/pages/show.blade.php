@extends('tfc.layouts.app')

@section('title', $page->title . ' | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">CORPORATE POLICY</span>
        <h1 class="display-5 fw-bold text-dark mb-2">{{ $page->title }}</h1>
        <p class="text-muted lead mb-0">TFC Sales &amp; Marketing &bull; Standards &amp; Guidelines</p>
    </div>
</section>

<section class="py-5" style="background-color: #F8FAFC;">
    <div class="container" style="max-width: 860px;">
        <div class="card p-4 p-md-5 border shadow-sm rounded-4 bg-white" style="border-radius: 18px !important;">
            <div class="text-dark policy-body" style="font-size: 1.02rem; line-height: 1.85;">
                {!! clean_html($page->content) !!}
            </div>
            <div class="mt-5 pt-3 border-top text-muted small d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>Last updated: {{ $page->updated_at ? $page->updated_at->format('d F Y') : date('d F Y') }}</span>
                <span>TFC Sales &amp; Marketing</span>
            </div>
        </div>
    </div>
</section>
@endsection
