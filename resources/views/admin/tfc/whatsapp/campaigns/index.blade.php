@extends('admin.layouts.app')

@section('title', 'TFC WhatsApp Broadcast Campaigns')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp</a></li>
                <li class="breadcrumb-item active">Campaigns</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="bi bi-broadcast text-success me-2"></i>TFC WhatsApp Broadcast Campaigns
        </h3>
        <p class="text-muted small mb-0">Broadcast certified direct-selling announcements, product launches, and seller communications via Meta Cloud API.</p>
    </div>
    <a href="{{ route('admin.tfc.whatsapp.campaigns.create') }}" class="btn btn-success shadow-sm">
        <i class="bi bi-plus-lg me-1"></i>Create Campaign
    </a>
</div>

{{-- Top Analytics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Total Campaigns</span>
                <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-megaphone fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalCampaigns) }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Created for TFC Sellers</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Messages Sent</span>
                <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-send-check fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalSent) }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Dispatched via Cloud API</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Delivery Rate</span>
                <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info">
                    <i class="bi bi-check2-all fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0 text-dark">{{ $overallDeliveryRate }}%</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Confirmed Meta Delivery</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold text-uppercase">Read Rate</span>
                <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-eye fs-5"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-0 text-dark">{{ $overallReadRate }}%</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Opened by recipients</span>
        </div>
    </div>
</div>

{{-- Campaigns List Table --}}
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Campaign Name</th>
                    <th>Template</th>
                    <th>Target Segment</th>
                    <th>Recipients</th>
                    <th>Delivery Status</th>
                    <th>Scheduled / Date</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campaigns as $camp)
                    @php
                        $statusBadge = match($camp->status) {
                            'completed' => 'bg-success',
                            'processing' => 'bg-info text-dark',
                            'scheduled' => 'bg-primary',
                            'failed' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        $pct = $camp->total_recipients > 0 ? round(($camp->delivered_count / $camp->total_recipients) * 100) : 0;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('admin.tfc.whatsapp.campaigns.show', $camp) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $camp->name }}
                            </a>
                            <div class="text-muted small" style="font-size: 0.72rem;">By: {{ $camp->creator?->name ?? 'Admin' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $camp->template_name }}</span>
                            <span class="text-muted small ms-1">({{ $camp->template_lang }})</span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ str_replace('_', ' ', ucwords($camp->target_segment, '_')) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ number_format($camp->total_recipients) }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Sent: {{ $camp->sent_count }}</div>
                        </td>
                        <td style="min-width: 140px;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge {{ $statusBadge }} py-1 px-2" style="font-size: 0.68rem;">{{ strtoupper($camp->status) }}</span>
                                <span class="text-muted small" style="font-size: 0.72rem;">{{ $pct }}%</span>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                            </div>
                        </td>
                        <td>
                            <div class="small text-dark">{{ $camp->scheduled_at ? $camp->scheduled_at->format('M d, Y h:i A') : $camp->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">{{ $camp->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.tfc.whatsapp.campaigns.show', $camp) }}" class="btn btn-sm btn-outline-dark">
                                View Analytics &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-broadcast display-5 text-muted opacity-50 d-block mb-3"></i>
                            <h5 class="fw-bold text-dark">No TFC WhatsApp Campaigns Yet</h5>
                            <p class="small text-muted mb-3">Launch broadcast announcements for product launches, seller conventions, and retail incentive updates.</p>
                            <a href="{{ route('admin.tfc.whatsapp.campaigns.create') }}" class="btn btn-sm btn-success">
                                <i class="bi bi-plus-lg me-1"></i>Create First Campaign
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($campaigns->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection
