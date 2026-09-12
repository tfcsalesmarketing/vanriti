@extends('admin.layouts.app')

@section('title', 'Return '.$returnRequest->return_number)

@section('content')
@php
    $classes = ['returned'=>'success','approved'=>'primary','pickup_scheduled'=>'info','picked_up'=>'info','requested'=>'secondary','under_review'=>'warning','rejected'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Return {{ $returnRequest->return_number }}</h5>
        <small class="text-muted">Requested on {{ $returnRequest->requested_at?->format('d M Y, h:i A') }}</small>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $classes[$returnRequest->status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $returnRequest->status)) }}</span>
        <a href="{{ route('admin.returns.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Customer</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $returnRequest->user?->name }}</div>
                <div class="text-muted">{{ $returnRequest->user?->email }}</div>
                <div class="text-muted">{{ $returnRequest->user?->phone }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Order</div>
            <div class="card-body small">
                @if ($returnRequest->order)
                    <a href="{{ route('admin.orders.show', $returnRequest->order) }}" class="text-decoration-none fw-semibold">{{ $returnRequest->order->order_number }}</a>
                    <div class="text-muted">{{ format_price($returnRequest->order->grand_total) }}</div>
                    <div class="text-muted">{{ ucwords(str_replace('_', ' ', $returnRequest->order->order_status)) }}</div>
                @else
                    <div class="text-muted">Order not found.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Details</div>
            <div class="card-body small">
                <div class="text-muted">Reason</div>
                <div class="fw-semibold">{{ $returnRequest->reason ?: '—' }}</div>
                @if ($returnRequest->description)
                    <div class="text-muted mt-2">Description</div>
                    <div>{{ $returnRequest->description }}</div>
                @endif
                @if ($returnRequest->admin_note)
                    <div class="text-muted mt-2">Admin Note</div>
                    <div>{{ $returnRequest->admin_note }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Items to Return</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Refund Amount</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($returnRequest->items as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $item->orderItem?->product_name ?: 'Item #'.$item->order_item_id }}</div>
                            <div class="text-muted small">{{ $item->orderItem?->variant_name }}</div>
                        </td>
                        <td class="text-center small">{{ $item->quantity }}</td>
                        <td class="text-end fw-semibold small">{{ format_price($item->refund_amount) }}</td>
                        <td class="small">{{ $item->condition ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end fw-semibold">Total Refund</td>
                    <td class="text-end fw-semibold">{{ format_price($returnRequest->items->sum('refund_amount')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if ($returnRequest->refunds->count())
    <div class="card mb-3">
        <div class="card-header">Refunds</div>
        <div class="card-body">
            @foreach ($returnRequest->refunds as $refund)
                <div class="d-flex justify-content-between small mb-2">
                    <div>
                        <span class="fw-semibold">{{ $refund->refund_number }}</span>
                        <span class="badge bg-{{ in_array($refund->status, ['completed']) ? 'success' : 'secondary' }}">{{ ucwords(str_replace('_', ' ', $refund->status)) }}</span>
                    </div>
                    <div class="fw-semibold">{{ format_price($refund->amount) }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header">Admin Actions</div>
    <div class="card-body">
        @if (in_array($returnRequest->status, ['requested', 'under_review']))
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.returns.reject', $returnRequest) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
                </form>
                <form method="POST" action="{{ route('admin.returns.request-info', $returnRequest) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-question-circle me-1"></i>Request Info</button>
                </form>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"><i class="bi bi-check-lg me-1"></i>Approve & Create Refund</button>
            </div>
        @elseif ($returnRequest->status === 'approved')
            <form method="POST" action="{{ route('admin.returns.pickup', $returnRequest) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-truck me-1"></i>Mark Pickup Scheduled</button>
            </form>
        @elseif (in_array($returnRequest->status, ['pickup_scheduled', 'picked_up']))
            <form method="POST" action="{{ route('admin.returns.complete', $returnRequest) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-box-seam me-1"></i>Mark Returned / Received</button>
            </form>
        @else
            <span class="text-muted small">No pending actions for this return.</span>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Timeline</div>
    <div class="card-body">
        <div class="small">
            <div class="d-flex gap-2 mb-2">
                <i class="bi bi-circle-fill mt-1" style="font-size:0.5rem;"></i>
                <div>
                    <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $returnRequest->status)) }}</div>
                    <div class="text-muted" style="font-size:0.75rem;">
                        {{ $returnRequest->requested_at?->format('d M Y, h:i A') }}
                        @if ($returnRequest->processed_at) · Processed {{ $returnRequest->processed_at->format('d M Y, h:i A') }} @endif
                    </div>
                </div>
            </div>
            @if ($returnRequest->admin_note)
                <div class="text-muted mt-1">Admin note: {{ $returnRequest->admin_note }}</div>
            @endif
        </div>
    </div>
</div>

@if (in_array($returnRequest->status, ['requested', 'under_review']))
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.returns.approve', $returnRequest) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Approve Return & Create Refund</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">
                            A refund of <strong>{{ format_price($returnRequest->items->sum('refund_amount')) }}</strong> will be created automatically for this return.
                        </p>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Admin Note (optional)</label>
                            <textarea name="admin_note" class="form-control form-control-sm" rows="3" placeholder="Note for the customer/admin"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success">Approve & Create Refund</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

{{-- ── ShipMojo Return Pickup Panel ───────────────────────────────── --}}
@if (setting('shipmojo_enabled'))
<div class="card mt-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-truck me-1 text-warning"></i> ShipMojo — Push Return Pickup
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            This will create a <strong>reverse pickup</strong> order in ShipMojo for return <strong>{{ $returnRequest->return_number }}</strong>.
            The customer's shipping address will be used as the pickup location.
        </p>
        <form method="POST" action="{{ route('admin.returns.shipmojo.push', $returnRequest) }}">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Return Reason</label>
                    <select name="return_reason_id" class="form-select form-select-sm" required>
                        <option value="">— Select Reason —</option>
                        <option value="9">Item is damaged</option>
                        <option value="10">Received wrong item</option>
                        <option value="11">Parcel damaged on arrival</option>
                        <option value="12">Size not as expected</option>
                        <option value="8">No longer needed</option>
                        <option value="1">Delivery date has changed</option>
                        <option value="13">Changed my mind</option>
                        <option value="14">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Customer Request</label>
                    <select name="customer_request" class="form-select form-select-sm" required>
                        <option value="REFUND">Refund</option>
                        <option value="REPLACEMENT">Replacement</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-warning"
                        onclick="return confirm('Push return pickup request to ShipMojo?')">
                        <i class="bi bi-cloud-upload me-1"></i> Push Return to ShipMojo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
{{-- ── End ShipMojo Return Panel ───────────────────────────────────── --}}

@endsection

