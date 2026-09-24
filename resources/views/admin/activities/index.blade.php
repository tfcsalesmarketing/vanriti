@extends('admin.layouts.app')

@section('title', 'Activity Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Activity Log</h5>
        <small class="text-muted">{{ $logs->total() }} activities</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.activities.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search description / action..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('admin.activities.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        $actorName = $log->actor_type === \App\Models\Admin::class
                            ? ($log->actor->name ?? 'System')
                            : ($log->actor->name ?? 'System');
                        $entityName = $log->entity ? class_basename($log->entity) . ' #' . $log->entity_id : '—';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded bg-light d-flex align-items-center justify-content-center fw-bold text-muted" style="width:32px;height:32px;">
                                    {{ strtoupper(substr($actorName, 0, 1)) }}
                                </div>
                                <span class="small fw-semibold">{{ $actorName }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ in_array($log->action, ['created', 'approved', 'activated']) ? 'badge-soft-success' : (in_array($log->action, ['deleted', 'rejected', 'suspended']) ? 'badge-soft-danger' : 'badge-soft-primary') }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td class="small text-muted">{{ $entityName }}</td>
                        <td>
                            <div class="small">{{ $log->description ?: '—' }}</div>
                            @if (!empty($log->old_values) || !empty($log->new_values))
                                <button type="button" class="btn btn-link btn-sm p-0 small" data-bs-toggle="collapse" data-bs-target="#logDetails_{{ $log->id }}">Show details</button>
                                <div class="collapse mt-2" id="logDetails_{{ $log->id }}">
                                    <div class="row g-2">
                                        @if (!empty($log->old_values))
                                            <div class="col-md-6">
                                                <div class="small text-muted fw-semibold mb-1">Old values</div>
                                                <pre class="bg-light rounded p-2 mb-0 small">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                        @if (!empty($log->new_values))
                                            <div class="col-md-6">
                                                <div class="small text-muted fw-semibold mb-1">New values</div>
                                                <pre class="bg-light rounded p-2 mb-0 small">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $log->ip_address ?: '—' }}</td>
                        <td class="small text-muted">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No activities found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
</div>
@endsection