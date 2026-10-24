@extends('admin.layouts.app')

@section('title', 'Campaign: ' . $campaign->name)

@section('content')
<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="btn btn-light-sm btn-icon rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold mb-0 text-dark">{{ $campaign->name }}</h4>
                    @php
                        $statusPills = [
                            'draft' => 'bg-secondary-subtle text-secondary',
                            'scheduled' => 'bg-info-subtle text-info',
                            'processing' => 'bg-primary-subtle text-primary',
                            'completed' => 'bg-success-subtle text-success',
                            'failed' => 'bg-danger-subtle text-danger',
                        ];
                    @endphp
                    <span class="badge {{ $statusPills[$campaign->status] ?? 'bg-light text-muted' }} rounded-pill px-3 py-1">
                        {{ ucfirst($campaign->status) }}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                    <span>Target: <strong>{{ ucfirst(str_replace('_', ' ', $campaign->target_segment)) }}</strong></span>
                    <span>•</span>
                    <span>Template: <code>{{ $campaign->template_name }}</code></span>
                    <span>•</span>
                    <span>Created {{ $campaign->created_at->format('d M, Y h:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($campaign->status !== 'completed' && $campaign->status !== 'processing')
                <form method="POST" action="{{ route('admin.whatsapp.campaigns.dispatch', $campaign->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-send-fill me-1"></i>Dispatch Broadcast Now
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Metrics KPI Grid --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2.4 col-lg">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem;">Total Audience</span>
                <h4 class="fw-bold text-dark mb-0 mt-1">{{ number_format($campaign->total_recipients) }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-2.4 col-lg">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem;">Sent</span>
                <h4 class="fw-bold text-primary mb-0 mt-1">{{ number_format($campaign->sent_count) }}</h4>
            </div>
        </div>
        <div class="col-6 col-md-2.4 col-lg">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem;">Delivered</span>
                <h4 class="fw-bold text-success mb-0 mt-1">{{ number_format($campaign->delivered_count) }}</h4>
                <span class="text-muted" style="font-size: 0.7rem;">{{ $campaign->deliveryRate() }}% rate</span>
            </div>
        </div>
        <div class="col-6 col-md-2.4 col-lg">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem;">Read</span>
                <h4 class="fw-bold text-info mb-0 mt-1">{{ number_format($campaign->read_count) }}</h4>
                <span class="text-muted" style="font-size: 0.7rem;">{{ $campaign->readRate() }}% of delivered</span>
            </div>
        </div>
        <div class="col-6 col-md-2.4 col-lg">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.65rem;">Failed</span>
                <h4 class="fw-bold text-danger mb-0 mt-1">{{ number_format($campaign->failed_count) }}</h4>
            </div>
        </div>
    </div>

    {{-- Delivery Progress Bar --}}
    @if ($campaign->total_recipients > 0)
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between mb-1.5 small">
                <span class="fw-bold text-dark">Broadcast Progress</span>
                <span class="text-muted">{{ $campaign->sent_count }}/{{ $campaign->total_recipients }} Dispatched</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $campaign->deliveryRate() }}%" title="Delivered: {{ $campaign->delivered_count }}"></div>
                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $campaign->sent_count > 0 ? round(($campaign->failed_count / $campaign->sent_count) * 100, 1) : 0 }}%" title="Failed: {{ $campaign->failed_count }}"></div>
            </div>
        </div>
    @endif

    {{-- Recipient Logs Table --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom p-3.5 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">Recipients & Delivery Logs</h6>
                <span class="badge bg-light text-muted">{{ $recipients->total() }} records</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Status Filter Tabs --}}
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.whatsapp.campaigns.show', [$campaign->id, 'search' => $search]) }}"
                       class="btn {{ empty($statusFilter) ? 'btn-dark' : 'btn-light' }}">All</a>
                    <a href="{{ route('admin.whatsapp.campaigns.show', [$campaign->id, 'status' => 'delivered', 'search' => $search]) }}"
                       class="btn {{ $statusFilter === 'delivered' ? 'btn-dark' : 'btn-light' }}">Delivered</a>
                    <a href="{{ route('admin.whatsapp.campaigns.show', [$campaign->id, 'status' => 'read', 'search' => $search]) }}"
                       class="btn {{ $statusFilter === 'read' ? 'btn-dark' : 'btn-light' }}">Read</a>
                    <a href="{{ route('admin.whatsapp.campaigns.show', [$campaign->id, 'status' => 'failed', 'search' => $search]) }}"
                       class="btn {{ $statusFilter === 'failed' ? 'btn-dark' : 'btn-light' }}">Failed</a>
                </div>

                {{-- Search Form --}}
                <form method="GET" action="{{ route('admin.whatsapp.campaigns.show', $campaign->id) }}">
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search phone, name...">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase" style="font-size: 0.72rem;">
                    <tr>
                        <th class="ps-4">Recipient</th>
                        <th>WhatsApp Phone</th>
                        <th>Delivery Status</th>
                        <th>Sent At</th>
                        <th>Delivered At</th>
                        <th>Read At</th>
                        <th class="pe-4">Error Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recipients as $rec)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-semibold text-dark">{{ $rec->customer_name ?: 'Customer' }}</span>
                            </td>
                            <td>
                                <code>+{{ $rec->phone }}</code>
                            </td>
                            <td>
                                @if ($rec->status === 'read')
                                    <span class="badge bg-info-subtle text-info rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-check2-all me-1"></i>Read
                                    </span>
                                @elseif ($rec->status === 'delivered')
                                    <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-check2-all me-1"></i>Delivered
                                    </span>
                                @elseif ($rec->status === 'sent')
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-check2 me-1"></i>Sent
                                    </span>
                                @elseif ($rec->status === 'failed')
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-x-circle me-1"></i>Failed
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-clock me-1"></i>Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ $rec->sent_at ? $rec->sent_at->format('h:i A') : '—' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $rec->delivered_at ? $rec->delivered_at->format('h:i A') : '—' }}</span>
                            </td>
                            <td>
                                <span class="text-muted small">{{ $rec->read_at ? $rec->read_at->format('h:i A') : '—' }}</span>
                            </td>
                            <td class="pe-4 text-danger small">
                                {{ $rec->error_message ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted small">
                                No recipients matching the current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($recipients->hasPages())
            <div class="p-3 border-top d-flex justify-content-center">
                {{ $recipients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
