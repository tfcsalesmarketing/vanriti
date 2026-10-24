@extends('admin.layouts.app')

@section('title', 'WhatsApp Chatbot & Quick Replies - Vanriti Admin')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 text-muted" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp CRM</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chatbot & Quick Replies</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: rgba(37, 211, 102, 0.15); color: #25D366;">
                    <i class="bi bi-robot fs-5"></i>
                </span>
                Chatbot & Quick Replies
            </h1>
            <p class="text-muted small mb-0">Automate customer conversations with keyword triggers and empower agents with 1-click canned snippets.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.whatsapp.inbox') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2">
                <i class="bi bi-chat-dots-fill me-1"></i> Open Live Inbox
            </a>
            @if ($tab === 'rules')
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#newRuleModal" style="background-color: #25D366; border-color: #25D366;">
                    <i class="bi bi-plus-lg me-1"></i> New Bot Rule
                </button>
            @else
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#newQuickReplyModal" style="background-color: #25D366; border-color: #25D366;">
                    <i class="bi bi-plus-lg me-1"></i> New Quick Reply
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Active Bot Rules</span>
                    <span class="p-2 rounded-3 bg-success-subtle text-success"><i class="bi bi-check2-circle fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ $activeRulesCount }} <span class="fs-6 text-muted fw-normal">/ {{ count($rules) }}</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Bot Interceptions</span>
                    <span class="p-2 rounded-3 bg-primary-subtle text-primary"><i class="bi bi-lightning-charge fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalHits) }}</h3>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Live Order Tracker</span>
                    <span class="p-2 rounded-3 bg-info-subtle text-info"><i class="bi bi-box-seam fs-6"></i></span>
                </div>
                <h4 class="fw-bold mb-0 text-success">Instant 24/7</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Quick Reply Snippets</span>
                    <span class="p-2 rounded-3 bg-warning-subtle text-warning"><i class="bi bi-chat-square-quote fs-6"></i></span>
                </div>
                <h3 class="fw-bold mb-0 text-dark">{{ $totalReplies }}</h3>
            </div>
        </div>
    </div>

    <!-- Bot Master Status Banner -->
    @php
        $botAutoReplyEnabled = (bool) setting('whatsapp_bot_auto_reply_enabled', false);
    @endphp

    @if (!$botAutoReplyEnabled)
        <div class="alert alert-warning border-0 rounded-4 shadow-sm p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="p-2.5 rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-shield-exclamation fs-5"></i></span>
                <div>
                    <div class="fw-bold text-dark">Chatbot Auto-Replies are currently PAUSED (Disabled)</div>
                    <div class="small text-muted">Inbound customer messages will sit cleanly in your Live Inbox for human staff without sending unsolicited bot replies.</div>
                </div>
            </div>
            <a href="{{ route('admin.settings.index') }}#pane-whatsapp" class="btn btn-dark btn-sm rounded-pill px-3 py-2 fw-semibold">
                <i class="bi bi-toggle-on text-success me-1"></i> Turn ON in Settings
            </a>
        </div>
    @else
        <div class="alert alert-success border-0 rounded-4 shadow-sm p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="p-2.5 rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-robot fs-5"></i></span>
                <div>
                    <div class="fw-bold text-success-emphasis">Chatbot Auto-Replies are ACTIVE</div>
                    <div class="small text-muted">Active keyword rules below will intercept incoming messages and send automated replies.</div>
                </div>
            </div>
            <a href="{{ route('admin.settings.index') }}#pane-whatsapp" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1.5 fw-semibold">
                <i class="bi bi-gear-fill me-1"></i> Manage in Settings
            </a>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $tab === 'rules' ? 'active bg-dark text-white' : 'bg-white text-muted shadow-sm' }}" 
               href="{{ route('admin.whatsapp.bot.index', ['tab' => 'rules']) }}">
                <i class="bi bi-cpu me-1.5"></i> Keyword Auto-Responders ({{ count($rules) }})
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 py-2 fw-semibold {{ $tab === 'quick_replies' ? 'active bg-dark text-white' : 'bg-white text-muted shadow-sm' }}" 
               href="{{ route('admin.whatsapp.bot.index', ['tab' => 'quick_replies']) }}">
                <i class="bi bi-lightning-charge me-1.5"></i> Canned Quick Replies ({{ $totalReplies }})
            </a>
        </li>
    </ul>

    @if ($tab === 'rules')
        <!-- TAB 1: Chatbot Auto-Responder Rules -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Active Keyword Trigger Rules</h5>
                    <p class="text-muted small mb-0">When an inbound customer message matches any keyword, the bot automatically sends an instant reply.</p>
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#newRuleModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Rule
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Rule Name</th>
                                <th>Match</th>
                                <th>Trigger Keywords</th>
                                <th>Action / Output</th>
                                <th>Cooldown</th>
                                <th>Hits</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">{{ $rule->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Priority: {{ $rule->priority }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                            {{ ucfirst(str_replace('_', ' ', $rule->match_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1" style="max-width: 280px;">
                                            @foreach (array_slice(explode(',', $rule->keywords), 0, 4) as $kw)
                                                <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-pill font-monospace" style="font-size: 0.72rem;">
                                                    {{ trim($kw) }}
                                                </span>
                                            @endforeach
                                            @if (count(explode(',', $rule->keywords)) > 4)
                                                <span class="badge bg-light text-muted border px-1.5 py-0.5 rounded-pill" style="font-size: 0.7rem;">
                                                    +{{ count(explode(',', $rule->keywords)) - 4 }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($rule->action_type === 'dynamic_order_tracking')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-box-seam me-1"></i> Live Order Lookup
                                            </span>
                                        @elseif ($rule->action_type === 'interactive_menu')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-menu-button-wide me-1"></i> Interactive Buttons
                                            </span>
                                        @elseif ($rule->action_type === 'catalog')
                                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-2.5 py-1" style="background-color: #f3e8ff; color: #7e22ce;">
                                                <i class="bi bi-shop me-1"></i> Product Catalog
                                            </span>
                                        @elseif ($rule->action_type === 'coupon')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1">
                                                <i class="bi bi-ticket-perforated me-1"></i> Active Coupon
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 text-truncate" style="max-width: 180px;">
                                                <i class="bi bi-chat-text me-1"></i> {{ \Illuminate\Support\Str::limit($rule->response_text, 25) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $rule->cooldown_minutes }}m</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark rounded-pill px-2.5 py-1 fw-bold">
                                            {{ number_format($rule->hit_count) }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.whatsapp.bot.rules.toggle', $rule) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-xs rounded-pill {{ $rule->is_active ? 'btn-success' : 'btn-outline-secondary' }}" style="font-size: 0.72rem; padding: 2px 10px;">
                                                {{ $rule->is_active ? 'Active' : 'Paused' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button type="button" class="btn btn-light btn-sm rounded-circle p-1.5" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editRuleModal{{ $rule->id }}"
                                                    title="Edit Rule">
                                                <i class="bi bi-pencil fs-6"></i>
                                            </button>
                                            <form action="{{ route('admin.whatsapp.bot.rules.destroy', $rule) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this chatbot rule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm text-danger rounded-circle p-1.5" title="Delete">
                                                    <i class="bi bi-trash fs-6"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Edit Modal for Rule -->
                                        <div class="modal fade text-start" id="editRuleModal{{ $rule->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <form action="{{ route('admin.whatsapp.bot.rules.update', $rule) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header border-0 pt-4 px-4 pb-2">
                                                            <h5 class="modal-title fw-bold">Edit Bot Rule</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body px-4">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Rule Name</label>
                                                                <input type="text" name="name" class="form-control rounded-3" value="{{ $rule->name }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Keywords (comma-separated)</label>
                                                                <textarea name="keywords" rows="2" class="form-control rounded-3" required>{{ $rule->keywords }}</textarea>
                                                            </div>
                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Match Type</label>
                                                                    <select name="match_type" class="form-select rounded-3">
                                                                        <option value="contains" {{ $rule->match_type === 'contains' ? 'selected' : '' }}>Contains Keyword</option>
                                                                        <option value="exact" {{ $rule->match_type === 'exact' ? 'selected' : '' }}>Exact Match</option>
                                                                        <option value="starts_with" {{ $rule->match_type === 'starts_with' ? 'selected' : '' }}>Starts With</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Action Type</label>
                                                                    <select name="action_type" class="form-select rounded-3">
                                                                        <option value="text" {{ $rule->action_type === 'text' ? 'selected' : '' }}>Custom Text</option>
                                                                        <option value="dynamic_order_tracking" {{ $rule->action_type === 'dynamic_order_tracking' ? 'selected' : '' }}>Dynamic Order Tracking</option>
                                                                        <option value="interactive_menu" {{ $rule->action_type === 'interactive_menu' ? 'selected' : '' }}>Interactive Menu</option>
                                                                        <option value="coupon" {{ $rule->action_type === 'coupon' ? 'selected' : '' }}>Active Coupon Offer</option>
                                                                        <option value="catalog" {{ $rule->action_type === 'catalog' ? 'selected' : '' }}>Product Catalogue</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Response Text (supports {name})</label>
                                                                <textarea name="response_text" rows="3" class="form-control rounded-3">{{ $rule->response_text }}</textarea>
                                                            </div>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Cooldown (mins)</label>
                                                                    <input type="number" name="cooldown_minutes" class="form-control rounded-3" value="{{ $rule->cooldown_minutes }}" min="0">
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Priority (0-999)</label>
                                                                    <input type="number" name="priority" class="form-control rounded-3" value="{{ $rule->priority }}" min="0">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 px-4 pb-4">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-dark rounded-pill px-4">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-robot fs-1 d-block mb-2 text-secondary"></i>
                                        No chatbot rules created yet. Click "+ New Bot Rule" above!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <!-- TAB 2: Quick Replies & Canned Snippets -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Agent Canned Snippets</h5>
                    <p class="text-muted small mb-0">Type <code class="text-success fw-bold">/</code> in the Live Inbox message composer or tap the ⚡ button to insert any snippet instantly.</p>
                </div>
                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#newQuickReplyModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Quick Reply
                </button>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Shortcut</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Snippet Preview</th>
                                <th>Usage</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($quickReplies as $qr)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-dark-subtle text-dark font-monospace rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.8rem;">
                                            /{{ $qr->shortcut }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small">
                                            {{ $qr->category ?: 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $qr->title }}</div>
                                    </td>
                                    <td>
                                        <div class="text-muted small text-truncate" style="max-width: 380px;">
                                            {{ $qr->content }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                            {{ number_format($qr->usage_count) }} uses
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button type="button" class="btn btn-light btn-sm rounded-circle p-1.5" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editQuickReplyModal{{ $qr->id }}"
                                                    title="Edit">
                                                <i class="bi bi-pencil fs-6"></i>
                                            </button>
                                            <form action="{{ route('admin.whatsapp.bot.quick-replies.destroy', $qr) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this snippet?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm text-danger rounded-circle p-1.5" title="Delete">
                                                    <i class="bi bi-trash fs-6"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Edit Modal for Quick Reply -->
                                        <div class="modal fade text-start" id="editQuickReplyModal{{ $qr->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <form action="{{ route('admin.whatsapp.bot.quick-replies.update', $qr) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header border-0 pt-4 px-4 pb-2">
                                                            <h5 class="modal-title fw-bold">Edit Quick Reply</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body px-4">
                                                            <div class="row g-2 mb-3">
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Shortcut (e.g. /greet)</label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text bg-light">/</span>
                                                                        <input type="text" name="shortcut" class="form-control rounded-end-3" value="{{ $qr->shortcut }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <label class="form-label fw-bold small text-dark">Category</label>
                                                                    <input type="text" name="category" class="form-control rounded-3" value="{{ $qr->category }}">
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Title</label>
                                                                <input type="text" name="title" class="form-control rounded-3" value="{{ $qr->title }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold small text-dark">Snippet Content (supports {name}, {site_url})</label>
                                                                <textarea name="content" rows="4" class="form-control rounded-3" required>{{ $qr->content }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 px-4 pb-4">
                                                            <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-dark rounded-pill px-4">Update Snippet</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-lightning-charge fs-1 d-block mb-2 text-secondary"></i>
                                        No quick replies added yet. Click "+ New Quick Reply" to add your first canned response!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal: New Bot Rule -->
<div class="modal fade" id="newRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.whatsapp.bot.rules.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4 pb-2">
                    <h5 class="modal-title fw-bold">Create Chatbot Trigger Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Rule Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Return Policy Query" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Keywords (comma-separated triggers) <span class="text-danger">*</span></label>
                        <textarea name="keywords" rows="2" class="form-control rounded-3" placeholder="return, exchange, damaged item, refund" required></textarea>
                        <div class="form-text small text-muted">Triggered whenever incoming message contains these keywords.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Match Mode</label>
                            <select name="match_type" class="form-select rounded-3">
                                <option value="contains" selected>Contains Keyword</option>
                                <option value="exact">Exact Match</option>
                                <option value="starts_with">Starts With</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Action / Response</label>
                            <select name="action_type" class="form-select rounded-3">
                                <option value="text" selected>Custom Text</option>
                                <option value="dynamic_order_tracking">Dynamic Order Lookup</option>
                                <option value="interactive_menu">Interactive Buttons Menu</option>
                                <option value="coupon">Active Coupon Offer</option>
                                <option value="catalog">Product Catalogue</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Response Text (supports {name})</label>
                        <textarea name="response_text" rows="3" class="form-control rounded-3" placeholder="Hello {name}! We offer a 7-day hassle-free replacement..."></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Cooldown (minutes)</label>
                            <input type="number" name="cooldown_minutes" class="form-control rounded-3" value="30" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Priority</label>
                            <input type="number" name="priority" class="form-control rounded-3" value="50" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4" style="background-color: #25D366; border-color: #25D366;">Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: New Quick Reply -->
<div class="modal fade" id="newQuickReplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.whatsapp.bot.quick-replies.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4 pb-2">
                    <h5 class="modal-title fw-bold">New Quick Reply Snippet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Shortcut <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">/</span>
                                <input type="text" name="shortcut" class="form-control rounded-end-3" placeholder="shipping" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-dark">Category</label>
                            <input type="text" name="category" class="form-control rounded-3" placeholder="e.g. Shipping" value="General">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Dispatch Timeline Notice" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Snippet Content <span class="text-danger">*</span></label>
                        <textarea name="content" rows="4" class="form-control rounded-3" placeholder="Hello {name}, our standard dispatch takes 24-48 hours..." required></textarea>
                        <div class="form-text small text-muted">Use <code>{name}</code> for customer's name and <code>{site_url}</code> for store URL.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4" style="background-color: #25D366; border-color: #25D366;">Save Snippet</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
