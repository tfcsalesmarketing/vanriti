@extends('tfc.layouts.app')

@section('title', ($page->meta_title ?: $page->title) . ' | TFC Sales & Marketing')
@section('meta_description', $page->meta_description ?: $page->excerpt)

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Statutory &amp; Legal Policy</span>
        <h1 class="display-5 fw-bold text-white mb-2">{{ $page->title }}</h1>
        <p class="text-white-50 lead mb-0">Consumer Protection (Direct Selling) Rules, 2021 &bull; Regulatory Compliance</p>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 900px;">
        @if ($isDraft)
            @if ($isAdmin)
                {{-- Admin Draft Preview Banner --}}
                <div class="tfc-draft-banner d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold"><i class="bi bi-shield-exclamation me-1"></i>ADMIN PREVIEW MODE: Document is in DRAFT Status</div>
                        <small>This document is hidden from public view until published in TFC Admin. Showing preview because you are logged in as an Administrator.</small>
                    </div>
                    <a href="{{ route('admin.tfc.pages.edit', $page) }}" class="btn btn-sm btn-warning text-dark fw-semibold text-nowrap">
                        <i class="bi bi-pencil-square me-1"></i>Edit in Admin
                    </a>
                </div>

                {{-- Admin Preview Content --}}
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white">
                    <div class="text-dark" style="font-size: 1.05rem; line-height: 1.8;">
                        {!! clean_html($page->content) !!}
                    </div>
                </div>
            @else
                {{-- Public Safeguard Notice per Requirements #1, #2, #8 --}}
                <div class="card p-5 border-0 shadow-sm rounded-4 text-center bg-white">
                    <div class="p-3 rounded-circle bg-warning bg-opacity-25 text-warning fs-1 d-inline-block mx-auto mb-3" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-file-earmark-lock-fill"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Statutory Disclosure Under Verification</h3>
                    <p class="text-muted lead fs-6 mx-auto mb-4" style="max-width: 620px;">
                        This document is currently undergoing formal corporate review in accordance with the <strong>Consumer Protection (Direct Selling) Rules, 2021 (and 2023 Amendment)</strong>. Incomplete officer designations and statutory disclosures remain unpublished until verified by authorized legal counsel.
                    </p>
                    <div class="p-3 bg-light rounded-3 text-start mx-auto mb-4" style="max-width: 580px;">
                        <h6 class="fw-bold small text-dark mb-1"><i class="bi bi-info-circle-fill text-primary me-1"></i>Statutory Compliance Notice:</h6>
                        <ul class="text-muted small mb-0 ps-3">
                            <li>TFC operates with <strong>Zero Registration Fee</strong> and prohibits pyramid schemes.</li>
                            <li>Grievances may be lodged 24/7 via the <a href="{{ tfc_route('grievance') }}" class="fw-semibold">Grievance Redressal Portal</a> (48h acknowledgment guarantee).</li>
                            <li>All orders and customer accounts are processed through authorized digital channels with nationwide express fulfillment.</li>
                        </ul>
                    </div>
                    <div>
                        <a href="{{ tfc_route('contact') }}" class="btn btn-tfc-navy me-2">Contact Compliance Desk</a>
                        <a href="{{ tfc_route('home') }}" class="btn btn-outline-secondary">Return Home</a>
                    </div>
                </div>
            @endif
        @else
            {{-- Published Verified Document --}}
            <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white">
                <div class="text-dark" style="font-size: 1.05rem; line-height: 1.8;">
                    {!! clean_html($page->content) !!}
                </div>
                <div class="mt-5 pt-3 border-top text-muted small">
                    Last updated on: {{ $page->updated_at->format('d F Y') }} &bull; TFC Sales &amp; Marketing Regulatory Affairs
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
