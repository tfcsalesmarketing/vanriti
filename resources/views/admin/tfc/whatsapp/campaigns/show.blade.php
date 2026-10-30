@extends('admin.layouts.app')

@section('title', 'TFC Campaign Analytics - ' . $campaign->name)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.campaigns.index') }}" class="text-decoration-none">Campaigns</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($campaign->name, 25) }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0 text-dark">{{ $campaign->name }}</h3>
            @php
                $statusBadge = match($campaign->status) {
                    'completed' => 'bg-success',
                    'processing' => 'bg-info text-dark',
                    'scheduled' => 'bg-primary',
                    'failed' => 'bg-danger',
                    default => 'bg-secondary',
                };
            @endphp
            <span class="badge {{ $statusBadge }} py-1 px-3 fs-6">{{ strtoupper($campaign->status) }}</span>
        </div>
        <p class="text-muted small mb-0">Created on {{ $campaign->created_at->format('M d, Y h:i A') }} by {{ $campaign->creator?->name ?? 'Admin' }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.tfc.whatsapp.campaigns.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Campaigns
        </a>
        @if (in_array($campaign->status, ['scheduled', 'processing', 'draft'], true))
            <form method="POST" action="{{ route('admin.tfc.whatsapp.campaigns.cancel', $campaign) }}" onsubmit="return confirm('Cancel this broadcast campaign?');">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Cancel Campaign
                </button>
            </form>
        @endif
    </div>
</div>

{{-- Analytics KPI Metric Cards --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Total Target</span>
            <h3 class="fw-bold mb-0 text-dark">{{ number_format($campaign->total_recipients) }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Segment: {{ str_replace('_', ' ', ucwords($campaign->target_segment, '_')) }}</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Dispatched</span>
            <h3 class="fw-bold mb-0 text-primary">{{ number_format($campaign->sent_count) }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Template: {{ $campaign->template_name }}</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Delivered</span>
            <h3 class="fw-bold mb-0 text-success">{{ number_format($campaign->delivered_count) }} ({{ $deliveryRate }}%)</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Meta delivery receipts</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Read Rate</span>
            <h3 class="fw-bold mb-0 text-warning">{{ number_format($campaign->read_count) }} ({{ $readRate }}%)</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Opened on WhatsApp</span>
        </div>
    </div>
</div>

{{-- Campaign Recipients Delivery Table --}}
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people me-1"></i>Recipient Delivery Receipts</h6>
        <span class="badge bg-light text-muted border">{{ number_format($recipients->total()) }} Total Records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Recipient Phone</th>
                    <th>Contact Name</th>
                    <th>Delivery Status</th>
                    <th>Meta Message ID</th>
                    <th>Sent At</th>
                    <th>Delivered At</th>
                    <th class="text-end pe-4">Chat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipients as $rec)
                    @php
                        $recBadge = match($rec->status) {
                            'read' => 'bg-info text-dark',
                            'delivered' => 'bg-success',
                            'sent' => 'bg-primary',
                            'failed' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <tr>
                        <td class="ps-4 fw-bold text-dark">+{{ $rec->phone }}</td>
                        <td>{{ $rec->customer_name ?: 'Recipient' }}</td>
                        <td>
                            <span class="badge {{ $recBadge }} py-1 px-2" style="font-size: 0.7rem;">
                                {{ strtoupper($rec->status) }}
                            </span>
                            @if ($rec->error_message)
                                <div class="text-danger small" style="font-size: 0.7rem;">{{ $rec->error_message }}</div>
                            @endif
                        </td>
                        <td class="small text-muted font-monospace">{{ $rec->meta_message_id ?: '-' }}</td>
                        <td class="small text-muted">{{ $rec->sent_at ? $rec->sent_at->format('M d, h:i A') : '-' }}</td>
                        <td class="small text-muted">{{ $rec->delivered_at ? $rec->delivered_at->format('M d, h:i A') : '-' }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.tfc.whatsapp.inbox', ['phone' => $rec->phone]) }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-chat-dots me-1"></i>Chat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No recipient records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($recipients->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $recipients->links() }}
        </div>
    @endif
</div>
@endsection
