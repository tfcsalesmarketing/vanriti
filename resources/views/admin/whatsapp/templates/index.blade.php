@extends('admin.layouts.app')

@section('title', 'Meta Approved Templates - Vanriti Admin')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp CRM</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Meta Templates</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: rgba(37, 211, 102, 0.15); color: #25D366;">
                    <i class="bi bi-file-earmark-check fs-5"></i>
                </span>
                Meta Approved Templates
            </h1>
            <p class="text-muted small mb-0">Official WhatsApp Cloud API templates for initiating chats and running broadcasts outside the 24-hour window.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.whatsapp.templates.sync') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2" title="Fetch latest approval statuses from Meta">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from Meta
                </button>
            </form>
            <a href="{{ route('admin.whatsapp.templates.create') }}" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold" style="background-color: #25D366; border-color: #25D366;">
                <i class="bi bi-plus-lg me-1"></i> Create Template
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-5 text-warning"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->has('waba_id'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-x-circle-fill fs-5 text-danger"></i>
            <div>{{ $errors->first('waba_id') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- WABA ID Configuration Card --}}
    @if (empty($wabaId))
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-warning border-4" style="background: #fffdf5;">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 44px; height: 44px; background: rgba(245, 158, 11, 0.15); color: #d97706;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                            <h5 class="fw-bold mb-0 text-dark">Action Required: Configure WhatsApp Business Account (WABA) ID</h5>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1">Missing Configuration</span>
                        </div>
                        <p class="text-muted small mb-3">
                            Meta Cloud API manages message templates at the <strong>WhatsApp Business Account (WABA)</strong> level, separate from the Phone Number ID. To sync approved templates or submit new ones, enter your 15–16 digit WABA ID below.
                        </p>

                        <div class="p-3 rounded-3 bg-white border mb-3 small">
                            <strong class="d-block text-dark mb-1"><i class="bi bi-question-circle me-1 text-primary"></i> Where to find your WABA ID:</strong>
                            <ol class="mb-0 ps-3 text-muted">
                                <li>Open <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener" class="fw-semibold text-primary text-decoration-none">Meta for Developers <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem;"></i></a> and select your App.</li>
                                <li>In the left sidebar, click <strong>WhatsApp &rarr; API Setup</strong>.</li>
                                <li>Under <strong>Step 1 (Send and receive messages)</strong>, look right below the Phone Number ID for <strong>WhatsApp Business Account ID</strong> (a 15–16 digit number).</li>
                            </ol>
                        </div>

                        <form action="{{ route('admin.whatsapp.templates.waba-id') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2">
                            @csrf
                            <div class="input-group" style="max-width: 420px;">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key-fill"></i></span>
                                <input type="text" name="waba_id" class="form-control font-monospace border-start-0" placeholder="e.g. 104829482719482" value="{{ old('waba_id') }}" required pattern="[0-9]{10,25}">
                            </div>
                            <button type="submit" class="btn btn-warning text-dark fw-semibold rounded-pill px-4">
                                <i class="bi bi-check-circle-fill me-1"></i> Save WABA ID & Sync
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light-subtle">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                        <i class="bi bi-check-circle me-1"></i> WABA Connected
                    </span>
                    <span class="small text-muted">
                        Account ID: <code class="text-dark fw-bold font-monospace">{{ $wabaId }}</code>
                    </span>
                </div>
                <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none p-0" data-bs-toggle="collapse" data-bs-target="#editWabaCollapse">
                    <i class="bi bi-pencil me-1"></i> Change WABA ID
                </button>
            </div>
            <div class="collapse px-3 pb-3" id="editWabaCollapse">
                <form action="{{ route('admin.whatsapp.templates.waba-id') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2 pt-2 border-top">
                    @csrf
                    <div class="input-group input-group-sm" style="max-width: 380px;">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                        <input type="text" name="waba_id" class="form-control font-monospace" value="{{ $wabaId }}" required pattern="[0-9]{10,25}">
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Update & Sync</button>
                </form>
            </div>
        </div>
    @endif

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Templates</span>
                    <span class="p-2 rounded-3 bg-light text-dark"><i class="bi bi-collection fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ $totalCount }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Approved (Ready)</span>
                    <span class="p-2 rounded-3 bg-success-subtle text-success"><i class="bi bi-check2-circle fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-success">{{ $approvedCount }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pending Review</span>
                    <span class="p-2 rounded-3 bg-warning-subtle text-warning"><i class="bi bi-hourglass-split fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-warning">{{ $pendingCount }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Rejected / Paused</span>
                    <span class="p-2 rounded-3 bg-danger-subtle text-danger"><i class="bi bi-x-circle fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-danger">{{ $rejectedCount }}</h3>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Filter Pills -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('admin.whatsapp.templates.index', ['status' => 'all', 'category' => $categoryFilter, 'search' => $search]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1.5 {{ $statusFilter === 'all' ? 'btn-dark' : 'btn-light border text-muted' }}">
                    All Status ({{ $totalCount }})
                </a>
                <a href="{{ route('admin.whatsapp.templates.index', ['status' => 'approved', 'category' => $categoryFilter, 'search' => $search]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1.5 {{ $statusFilter === 'approved' ? 'btn-success text-white' : 'btn-light border text-muted' }}">
                    <i class="bi bi-check2-circle me-1 text-success"></i> Approved ({{ $approvedCount }})
                </a>
                <a href="{{ route('admin.whatsapp.templates.index', ['status' => 'pending', 'category' => $categoryFilter, 'search' => $search]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1.5 {{ $statusFilter === 'pending' ? 'btn-warning text-dark' : 'btn-light border text-muted' }}">
                    <i class="bi bi-hourglass me-1 text-warning"></i> Pending ({{ $pendingCount }})
                </a>
                <a href="{{ route('admin.whatsapp.templates.index', ['status' => 'rejected', 'category' => $categoryFilter, 'search' => $search]) }}"
                   class="btn btn-sm rounded-pill px-3 py-1.5 {{ $statusFilter === 'rejected' ? 'btn-danger text-white' : 'btn-light border text-muted' }}">
                    <i class="bi bi-x-circle me-1 text-danger"></i> Rejected ({{ $rejectedCount }})
                </a>
            </div>

            <!-- Search input -->
            <form method="GET" action="{{ route('admin.whatsapp.templates.index') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <input type="hidden" name="category" value="{{ $categoryFilter }}">
                <div class="input-group input-group-sm" style="width: 240px;">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Search template..." value="{{ $search }}">
                    @if ($search)
                        <a href="{{ route('admin.whatsapp.templates.index', ['status' => $statusFilter, 'category' => $categoryFilter]) }}" class="input-group-text bg-light text-muted text-decoration-none">&times;</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="row g-4">
        @forelse ($templates as $tmpl)
            @php
                $badge = $tmpl->status_badge;
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden d-flex flex-column transition-all hover-shadow">
                    <!-- Top Ribbon -->
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light-subtle">
                        <div>
                            <span class="badge {{ $badge['class'] }} rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.75rem;">
                                <i class="bi {{ $badge['icon'] }} me-1"></i> {{ $badge['label'] }}
                            </span>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-1 ms-1 font-monospace" style="font-size: 0.7rem;">
                                {{ strtoupper($tmpl->language) }}
                            </span>
                        </div>
                        <span class="badge bg-dark-subtle text-dark rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            {{ $tmpl->category }}
                        </span>
                    </div>

                    <!-- Template Card Body -->
                    <div class="card-body p-3 flex-grow-1">
                        <h6 class="fw-bold text-dark font-monospace mb-2 text-truncate" title="{{ $tmpl->name }}">
                            {{ $tmpl->name }}
                        </h6>

                        <!-- Simulated WhatsApp Message Bubble Preview -->
                        <div class="p-3 rounded-3 bg-light text-dark position-relative border" style="font-size: 0.84rem; line-height: 1.45;">
                            @if ($tmpl->header_type === 'IMAGE')
                                <div class="mb-2 rounded bg-secondary-subtle text-muted text-center py-3">
                                    <i class="bi bi-image fs-4 d-block mb-1"></i>
                                    <span style="font-size: 0.72rem;">Header Media (Image)</span>
                                </div>
                            @elseif ($tmpl->header_type === 'TEXT' && $tmpl->header_text)
                                <div class="fw-bold text-dark mb-1.5">{{ $tmpl->header_text }}</div>
                            @endif

                            <div class="text-secondary mb-2" style="white-space: pre-line;">{{ $tmpl->body_text }}</div>

                            @if ($tmpl->footer_text)
                                <div class="text-muted mt-2 pt-1 border-top" style="font-size: 0.72rem;">{{ $tmpl->footer_text }}</div>
                            @endif

                            @if (!empty($tmpl->buttons))
                                <div class="mt-2 pt-2 border-top d-flex flex-column gap-1">
                                    @foreach ($tmpl->buttons as $btn)
                                        <div class="btn btn-sm btn-white border text-primary fw-medium text-center py-1 rounded" style="font-size: 0.75rem;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> {{ $btn['text'] ?? 'Action' }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($tmpl->rejected_reason)
                            <div class="alert alert-danger py-1.5 px-2 mt-2 mb-0 rounded-3 small" style="font-size: 0.75rem;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Rejection Reason:</strong> {{ $tmpl->rejected_reason }}
                            </div>
                        @endif
                    </div>

                    <!-- Card Actions Footer -->
                    <div class="p-3 border-top bg-white d-flex align-items-center justify-content-between mt-auto">
                        <div class="text-muted" style="font-size: 0.72rem;">
                            @if ($tmpl->variable_count > 0)
                                <i class="bi bi-code-slash me-0.5 text-primary"></i> {{ $tmpl->variable_count }} {{ $tmpl->variable_count === 1 ? 'variable' : 'variables' }}
                            @else
                                <span class="text-muted">Static template</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            @if ($tmpl->isApproved())
                                <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-megaphone me-1"></i> Broadcast
                                </a>
                            @endif
                            <form action="{{ route('admin.whatsapp.templates.destroy', $tmpl) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete template {{ $tmpl->name }} from Meta?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-light text-danger rounded-circle p-1.5" title="Delete Template">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                    <i class="bi bi-file-earmark-check fs-1 text-muted opacity-50 d-block mb-2"></i>
                    <h5 class="fw-bold text-dark">No templates found</h5>
                    <p class="text-muted small mb-3">Sync approved templates from your Meta Business Account or build a new one.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('admin.whatsapp.templates.sync') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                <i class="bi bi-arrow-repeat me-1"></i> Sync from Meta
                            </button>
                        </form>
                        <a href="{{ route('admin.whatsapp.templates.create') }}" class="btn btn-success btn-sm rounded-pill px-3" style="background-color: #25D366; border-color: #25D366;">
                            <i class="bi bi-plus-lg me-1"></i> Create Template
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $templates->links() }}
    </div>
</div>
@endsection
