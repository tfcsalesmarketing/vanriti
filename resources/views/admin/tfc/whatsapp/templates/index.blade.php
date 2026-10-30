@extends('admin.layouts.app')

@section('title', 'TFC Meta WhatsApp Templates')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp</a></li>
                <li class="breadcrumb-item active">Meta Templates</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="bi bi-file-earmark-code text-primary me-2"></i>TFC Meta WhatsApp Templates
        </h3>
        <p class="text-muted small mb-0">Browse and synchronize approved message templates registered under your WhatsApp Business Account (WABA).</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form method="POST" action="{{ route('admin.tfc.whatsapp.templates.sync') }}">
            @csrf
            <button type="submit" class="btn btn-primary shadow-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Sync from Meta API
            </button>
        </form>
    </div>
</div>

{{-- Metric Counters --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Total Templates</span>
            <h3 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Registered in WABA</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Approved &amp; Live</span>
            <h3 class="fw-bold mb-0 text-success">{{ $stats['approved'] }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Ready to broadcast</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Pending Meta Review</span>
            <h3 class="fw-bold mb-0 text-warning">{{ $stats['pending'] }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Awaiting 24h approval</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Rejected</span>
            <h3 class="fw-bold mb-0 text-danger">{{ $stats['rejected'] }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Needs policy correction</span>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.tfc.whatsapp.templates.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search template name or text..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-12 col-md-4">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Approval Statuses</option>
                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>APPROVED Only</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>PENDING Only</option>
                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>REJECTED Only</option>
            </select>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <button type="submit" class="btn btn-sm btn-dark">Filter</button>
            @if ($search || $status !== 'all')
                <a href="{{ route('admin.tfc.whatsapp.templates.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>
</div>

{{-- Templates Grid --}}
<div class="row g-3">
    @forelse ($templates as $tmpl)
        @php
            $badgeColor = match($tmpl->status) {
                'APPROVED' => 'bg-success',
                'PENDING' => 'bg-warning text-dark',
                'REJECTED' => 'bg-danger',
                default => 'bg-secondary',
            };
        @endphp
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <span class="badge bg-light text-dark border font-monospace small">{{ $tmpl->name }}</span>
                    <span class="badge {{ $badgeColor }} py-1 px-2" style="font-size: 0.65rem;">{{ $tmpl->status }}</span>
                </div>

                <div class="text-muted small mb-2">
                    <span class="badge bg-secondary-subtle text-secondary me-1">{{ $tmpl->category }}</span>
                    <span>Lang: <strong>{{ $tmpl->language }}</strong></span>
                    @if ($tmpl->header_type)
                        &bull; <span>Header: {{ $tmpl->header_type }}</span>
                    @endif
                </div>

                <div class="bg-light p-3 rounded-3 small flex-grow-1 text-secondary mb-3" style="white-space: pre-wrap; max-height: 140px; overflow-y: auto; font-size: 0.8rem;">
                    {{ $tmpl->body_text }}
                </div>

                @if ($tmpl->variable_count > 0)
                    <div class="text-muted mb-3" style="font-size: 0.72rem;">
                        <i class="bi bi-code-square me-1"></i>Variables required: <strong>{{ $tmpl->variable_count }}</strong>
                    </div>
                @endif

                <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted" style="font-size: 0.7rem;">Synced: {{ $tmpl->synced_at ? $tmpl->synced_at->diffForHumans() : 'Never' }}</span>
                    <a href="{{ route('admin.tfc.whatsapp.campaigns.create') }}" class="btn btn-xs btn-outline-success py-1 px-2" style="font-size: 0.72rem;">
                        <i class="bi bi-broadcast me-1"></i>Use in Campaign
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-file-earmark-code display-5 opacity-50 d-block mb-3"></i>
            <h5 class="fw-bold text-dark">No Templates Found</h5>
            <p class="small text-muted mb-3">Click 'Sync from Meta API' to retrieve all pre-approved templates registered in your WhatsApp Business Account.</p>
            <form method="POST" action="{{ route('admin.tfc.whatsapp.templates.sync') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i>Sync Templates Now
                </button>
            </form>
        </div>
    @endforelse
</div>

@if ($templates->hasPages())
    <div class="p-3 border-top mt-4 d-flex justify-content-end">
        {{ $templates->links() }}
    </div>
@endif

@endsection
