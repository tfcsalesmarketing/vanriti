@extends('admin.layouts.app')

@section('title', 'WhatsApp Broadcast Campaigns')

@section('content')
<div class="container-fluid px-0">
    {{-- Header & Primary Action --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-megaphone-fill text-success"></i>
                <span>WhatsApp Broadcast Campaigns</span>
            </h4>
            <p class="text-muted small mb-0">Launch segmented bulk notifications, abandoned cart reminders, and promotional offers.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.whatsapp.inbox') }}" class="btn btn-light-sm">
                <i class="bi bi-chat-dots me-1"></i>Open Live Inbox
            </a>
            <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="btn btn-success fw-semibold shadow-sm rounded-pill px-3.5">
                <i class="bi bi-plus-lg me-1"></i>Create New Campaign
            </a>
        </div>
    </div>

    {{-- Analytics KPI Overview Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-success-subtle text-success fs-4">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Campaigns</span>
                        <h3 class="fw-bold text-dark mb-0 mt-0.5">{{ number_format($totalCampaigns) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary fs-4">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Messages Dispatched</span>
                        <h3 class="fw-bold text-dark mb-0 mt-0.5">{{ number_format($totalSent) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-emerald-subtle text-emerald fs-4">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Avg Delivery Rate</span>
                        <h3 class="fw-bold text-dark mb-0 mt-0.5">{{ $overallDeliveryRate }}%</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-info-subtle text-info fs-4">
                        <i class="bi bi-eye"></i>
                    </div>
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Avg Read Rate</span>
                        <h3 class="fw-bold text-dark mb-0 mt-0.5">{{ $overallReadRate }}%</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Campaigns Data Table --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom p-3.5 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0 text-dark">Campaigns Directory</h6>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-4">Campaign Name</th>
                        <th>Target Cohort</th>
                        <th>Meta Template</th>
                        <th>Recipients</th>
                        <th>Delivery Status</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $camp)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('admin.whatsapp.campaigns.show', $camp->id) }}" class="fw-bold text-dark text-decoration-none hover-underline">
                                    {{ $camp->name }}
                                </a>
                                @if ($camp->notes)
                                    <div class="text-muted small text-truncate" style="max-width: 200px;">{{ $camp->notes }}</div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $cohortLabels = [
                                        'all' => ['All Customers', 'bg-secondary-subtle text-secondary'],
                                        'abandoned_cart' => ['Abandoned Carts', 'bg-warning-subtle text-warning-emphasis'],
                                        'buyers' => ['Recent Buyers / VIPs', 'bg-success-subtle text-success'],
                                        'inactive' => ['Inactive (30d+)', 'bg-info-subtle text-info'],
                                    ];
                                    $cohort = $cohortLabels[$camp->target_segment] ?? [$camp->target_segment, 'bg-light text-muted'];
                                @endphp
                                <span class="badge {{ $cohort[1] }} rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                    {{ $cohort[0] }}
                                </span>
                            </td>
                            <td>
                                <code class="text-dark bg-light px-2 py-1 rounded small">{{ $camp->template_name }}</code>
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ number_format($camp->total_recipients) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusPills = [
                                        'draft' => 'bg-secondary-subtle text-secondary',
                                        'scheduled' => 'bg-info-subtle text-info',
                                        'processing' => 'bg-primary-subtle text-primary',
                                        'completed' => 'bg-success-subtle text-success',
                                        'failed' => 'bg-danger-subtle text-danger',
                                    ];
                                @endphp
                                <span class="badge {{ $statusPills[$camp->status] ?? 'bg-light text-muted' }} rounded-pill px-2.5 py-1 mb-1" style="font-size: 0.7rem;">
                                    {{ ucfirst($camp->status) }}
                                </span>

                                @if ($camp->total_recipients > 0)
                                    <div class="progress" style="height: 4px; width: 100px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $camp->deliveryRate() }}%" title="Delivered: {{ $camp->deliveryRate() }}%"></div>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.65rem;">{{ $camp->delivered_count }}/{{ $camp->total_recipients }} delivered</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ $camp->created_at->format('d M, Y') }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('admin.whatsapp.campaigns.show', $camp->id) }}" class="btn btn-light-sm btn-icon rounded-circle" title="View Campaign Details & Recipient Log">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-megaphone fs-1 d-block mb-2 text-muted opacity-50"></i>
                                <div class="fw-semibold">No WhatsApp campaigns created yet</div>
                                <p class="small text-muted mb-3">Reach your customer segments with high-converting Meta approved broadcasts.</p>
                                <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="btn btn-sm btn-success rounded-pill px-3">
                                    <i class="bi bi-plus-lg me-1"></i>Create First Campaign
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($campaigns->hasPages())
            <div class="p-3 border-top d-flex justify-content-center">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
