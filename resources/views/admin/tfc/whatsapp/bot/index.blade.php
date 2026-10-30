@extends('admin.layouts.app')

@section('title', 'TFC WhatsApp Chatbot & Quick Replies')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.whatsapp.inbox') }}" class="text-decoration-none">WhatsApp</a></li>
                <li class="breadcrumb-item active">Chatbot &amp; Quick Replies</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="bi bi-robot text-success me-2"></i>TFC WhatsApp Chatbot &amp; Quick Replies
        </h3>
        <p class="text-muted small mb-0">Configure automated keyword responders for direct-seller inquiries and manage live chat quick canned replies.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if ($tab === 'rules')
            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#newRuleModal">
                <i class="bi bi-plus-lg me-1"></i>New Auto-Responder Rule
            </button>
        @else
            <button type="button" class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#newReplyModal">
                <i class="bi bi-plus-lg me-1"></i>New Quick Reply
            </button>
        @endif
    </div>
</div>

{{-- Top Summary Stats --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Active Bot Rules</span>
            <h3 class="fw-bold mb-0 text-dark">{{ $activeRulesCount }} / {{ $rules->count() }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Direct-Selling auto responders</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Total Bot Triggers</span>
            <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalHits) }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Inbound automated replies</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Agent Canned Replies</span>
            <h3 class="fw-bold mb-0 text-success">{{ $totalReplies }}</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">One-click live chat snippets</span>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <span class="text-muted small text-uppercase fw-semibold">Rule 7 Redressal Bot</span>
            <h3 class="fw-bold mb-0 text-warning"><i class="bi bi-shield-check"></i> Active</h3>
            <span class="text-muted small" style="font-size: 0.75rem;">Automated grievance routing</span>
        </div>
    </div>
</div>

{{-- Nav Tabs --}}
<ul class="nav nav-pills mb-3" id="pills-tab">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'rules' ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.bot.index', ['tab' => 'rules']) }}">
            <i class="bi bi-robot me-1"></i>Chatbot Rules ({{ $rules->count() }})
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'replies' ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.bot.index', ['tab' => 'replies']) }}">
            <i class="bi bi-lightning-charge me-1"></i>Quick Replies Library ({{ $quickReplies->count() }})
        </a>
    </li>
</ul>

@if ($tab === 'rules')
    {{-- Chatbot Rules Tab Content --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Rule Name</th>
                        <th>Triggers / Keywords</th>
                        <th>Action Type</th>
                        <th>Response Preview</th>
                        <th>Trigger Count</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $rule->name }}</td>
                            <td>
                                @foreach (array_slice(explode(',', $rule->keywords), 0, 3) as $kw)
                                    <span class="badge bg-light text-dark border px-2 py-1 mb-1 font-monospace" style="font-size: 0.7rem;">
                                        {{ trim($kw) }}
                                    </span>
                                @endforeach
                                @if (count(explode(',', $rule->keywords)) > 3)
                                    <span class="text-muted small">+{{ count(explode(',', $rule->keywords)) - 3 }}</span>
                                @endif
                                <div class="text-muted" style="font-size: 0.68rem;">Match: {{ ucfirst($rule->match_type) }}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                    {{ strtoupper($rule->action_type) }}
                                </span>
                            </td>
                            <td style="max-width: 250px;">
                                <div class="small text-truncate" title="{{ $rule->response_text }}">
                                    {{ $rule->response_text ?: 'Interactive / Dynamic flow' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill">{{ number_format($rule->hit_count) }}</span>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input rule-toggle-switch cursor-pointer" type="checkbox"
                                           data-url="{{ route('admin.tfc.whatsapp.bot.rules.toggle', $rule) }}"
                                           {{ $rule->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" action="{{ route('admin.tfc.whatsapp.bot.rules.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('Delete this chatbot rule?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-robot display-5 text-muted opacity-50 d-block mb-3"></i>
                                <h5 class="fw-bold text-dark">No TFC Chatbot Rules Configured</h5>
                                <p class="small text-muted mb-3">Set up instant automated responses for words like "join", "seller", "products", "opportunity", or "grievance".</p>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newRuleModal">
                                    <i class="bi bi-plus-lg me-1"></i>Create First Rule
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    {{-- Quick Replies Tab Content --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Shortcut</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Snippet Content</th>
                        <th>Times Used</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quickReplies as $reply)
                        <tr>
                            <td class="ps-4 fw-bold font-monospace text-success">/{{ $reply->shortcut }}</td>
                            <td class="fw-semibold text-dark">{{ $reply->title }}</td>
                            <td>
                                <span class="badge bg-light text-secondary border">{{ $reply->category }}</span>
                            </td>
                            <td style="max-width: 320px;">
                                <div class="small text-truncate" title="{{ $reply->content }}">{{ $reply->content }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary rounded-pill">{{ $reply->usage_count }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" action="{{ route('admin.tfc.whatsapp.bot.quick-replies.destroy', $reply) }}" class="d-inline" onsubmit="return confirm('Delete this quick reply?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-lightning-charge display-5 text-muted opacity-50 d-block mb-3"></i>
                                <h5 class="fw-bold text-dark">No Quick Replies Saved</h5>
                                <p class="small text-muted mb-3">Save canned answers to prospective seller FAQs and grievance guidelines for 1-click live chat.</p>
                                <button type="button" class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#newReplyModal">
                                    <i class="bi bi-plus-lg me-1"></i>Create First Quick Reply
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ====================================================================== --}}
{{-- MODALS: Create Rule & Create Quick Reply                              --}}
{{-- ====================================================================== --}}

{{-- New Rule Modal --}}
<div class="modal fade" id="newRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('admin.tfc.whatsapp.bot.rules.store') }}" class="modal-content border-0 shadow rounded-4">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-robot text-success me-2"></i>Create Chatbot Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Rule Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Prospective Seller Info" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Match Logic</label>
                        <select name="match_type" class="form-select">
                            <option value="contains" selected>Contains keyword</option>
                            <option value="exact">Exact match only</option>
                            <option value="starts_with">Starts with keyword</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Trigger Keywords (comma-separated) <span class="text-danger">*</span></label>
                        <input type="text" name="keywords" class="form-control font-monospace" placeholder="join, seller, direct selling, distributor, opportunity" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Action Type</label>
                        <select name="action_type" class="form-select">
                            <option value="text" selected>Send Custom Text Response</option>
                            <option value="interactive_menu">Interactive Reply Buttons</option>
                            <option value="catalog">Show VANRITI Herbal Catalog</option>
                            <option value="dynamic_order_tracking">Dynamic Order / Status Tracking</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-dark">Cooldown Minutes</label>
                        <input type="number" name="cooldown_minutes" class="form-control" value="30" min="0" max="1440">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Response Text Body <span class="text-danger">*</span></label>
                        <textarea name="response_text" class="form-control" rows="4" placeholder="Hello! Welcome to TFC Sales &amp; Marketing. We are a customer-first direct-selling company founded on genuine VANRITI Ayurvedic wellness formulations..." required></textarea>
                    </div>

                    {{-- Interactive Reply Buttons (Up to 3) --}}
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark">Interactive Quick Buttons (Optional, max 3)</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="text" name="button_1_title" class="form-control form-control-sm" placeholder="Button 1 (e.g., Become a Seller)">
                            </div>
                            <div class="col-4">
                                <input type="text" name="button_2_title" class="form-control form-control-sm" placeholder="Button 2 (e.g., View Products)">
                            </div>
                            <div class="col-4">
                                <input type="text" name="button_3_title" class="form-control form-control-sm" placeholder="Button 3 (e.g., Talk to Advisor)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm px-4">Save Rule</button>
            </div>
        </form>
    </div>
</div>

{{-- New Quick Reply Modal --}}
<div class="modal fade" id="newReplyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.tfc.whatsapp.bot.quick-replies.store') }}" class="modal-content border-0 shadow rounded-4">
            @csrf
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-lightning-charge text-warning me-2"></i>New Quick Reply Snippet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Shortcut Command (Type in live chat) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">/</span>
                        <input type="text" name="shortcut" class="form-control font-monospace" placeholder="seller_plan" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Snippet Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Direct Seller Program Guidelines" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="Direct Selling" value="Direct Selling">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Snippet Content <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="4" placeholder="Hello! Thank you for inquiring about direct selling with TFC Sales &amp; Marketing..." required></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark btn-sm px-4">Save Quick Reply</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.rule-toggle-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const url = this.dataset.url;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                }
            })
            .catch(() => {
                this.checked = !this.checked;
            });
        });
    });
});
</script>
@endpush
