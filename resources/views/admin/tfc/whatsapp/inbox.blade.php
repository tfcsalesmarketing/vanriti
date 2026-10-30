@extends('admin.layouts.app')

@section('title', 'TFC WhatsApp Live Chat Inbox')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.tfc.dashboard') }}" class="text-decoration-none">TFC Admin</a></li>
                <li class="breadcrumb-item active">WhatsApp Inbox</li>
            </ol>
        </nav>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="bi bi-whatsapp text-success me-2"></i>TFC WhatsApp Live Inbox
        </h3>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.tfc.whatsapp.campaigns.create') }}" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-broadcast me-1"></i>New Campaign
        </a>
        <a href="{{ route('admin.tfc.whatsapp.profile') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-gear me-1"></i>API Settings
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="height: calc(100vh - 190px); min-height: 600px;">
    <div class="row g-0 h-100">
        {{-- ============================================================== --}}
        {{-- LEFT COLUMN: Conversations List                                --}}
        {{-- ============================================================== --}}
        <div class="col-12 col-md-4 col-lg-3 border-end d-flex flex-column h-100 bg-white">
            {{-- Search & Filters --}}
            <div class="p-3 border-bottom bg-light">
                <form method="GET" action="{{ route('admin.tfc.whatsapp.inbox') }}" class="mb-2">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search phone or name..." value="{{ $search }}">
                        @if ($search)
                            <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => $filter]) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                        @endif
                    </div>
                </form>

                {{-- Filter Chips --}}
                <div class="d-flex gap-1 overflow-auto pb-1" style="scrollbar-width: thin;">
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'tfc', 'search' => $search]) }}" class="btn btn-xs rounded-pill {{ $filter === 'tfc' ? 'btn-dark' : 'btn-outline-secondary' }} text-nowrap" style="font-size: 0.72rem; padding: 0.2rem 0.6rem;">
                        TFC ({{ $conversations->total() }})
                    </a>
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'unread', 'search' => $search]) }}" class="btn btn-xs rounded-pill {{ $filter === 'unread' ? 'btn-danger' : 'btn-outline-danger' }} text-nowrap" style="font-size: 0.72rem; padding: 0.2rem 0.6rem;">
                        Unread @if($unreadCount > 0)({{ $unreadCount }})@endif
                    </a>
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'inquiry', 'search' => $search]) }}" class="btn btn-xs rounded-pill {{ $filter === 'inquiry' ? 'btn-primary' : 'btn-outline-primary' }} text-nowrap" style="font-size: 0.72rem; padding: 0.2rem 0.6rem;">
                        Sellers &amp; Inquiries
                    </a>
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'grievance', 'search' => $search]) }}" class="btn btn-xs rounded-pill {{ $filter === 'grievance' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' }} text-nowrap" style="font-size: 0.72rem; padding: 0.2rem 0.6rem;">
                        Grievances
                    </a>
                </div>
            </div>

            {{-- Conversations List Items --}}
            <div class="overflow-auto flex-grow-1" id="tfcConversationList">
                @forelse ($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                    @endphp
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['conversation_id' => $conv->id, 'filter' => $filter, 'search' => $search]) }}"
                       class="d-block p-3 border-bottom text-decoration-none transition {{ $isActive ? 'bg-light border-start border-4 border-primary' : 'text-dark hover-bg-light' }}">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                     style="width: 36px; height: 36px; min-width: 36px; font-size: 0.85rem; background: {{ $conv->unread_count > 0 ? '#198754' : '#0A1D37' }};">
                                    {{ strtoupper(substr($conv->contact_name ?: $conv->phone, 0, 1)) }}
                                </div>
                                <div class="text-truncate">
                                    <div class="fw-semibold text-truncate small text-dark mb-0">
                                        {{ $conv->contact_name ?: '+' . $conv->phone }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">+{{ $conv->phone }}</div>
                                </div>
                            </div>
                            <div class="text-end text-nowrap">
                                <div class="text-muted" style="font-size: 0.68rem;">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}
                                </div>
                                @if ($conv->unread_count > 0)
                                    <span class="badge bg-success rounded-pill mt-1" style="font-size: 0.65rem;">{{ $conv->unread_count }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Preview & Tags --}}
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-muted text-truncate pe-2" style="font-size: 0.76rem;">
                                {{ $conv->last_message_preview ?: 'No messages yet' }}
                            </div>
                            @if ($conv->tfc_inquiry_id)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.62rem;">Seller App</span>
                            @elseif ($conv->tfc_grievance_id)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.62rem;">Rule 7</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-chat-left-dots display-6 opacity-50 d-block mb-2"></i>
                        <p class="small mb-0">No conversations found</p>
                    </div>
                @endforelse
            </div>

            {{-- Quick New Chat Initiator --}}
            <div class="p-2 border-top bg-light">
                <button type="button" class="btn btn-sm btn-outline-dark w-100 rounded-3" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <i class="bi bi-plus-circle me-1"></i>Start New Chat
                </button>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- CENTER COLUMN: Chat Messages Thread & Input Area               --}}
        {{-- ============================================================== --}}
        @if ($activeConversation)
            <div class="col-12 col-md-5 col-lg-6 d-flex flex-column h-100 bg-white">
                {{-- Header --}}
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-white">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                             style="width: 40px; height: 40px; background: #0A1D37;">
                            {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                <span>{{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }}</span>
                                @if ($activeConversation->tfc_inquiry_id)
                                    <span class="badge bg-primary rounded-pill small" style="font-size: 0.65rem;">Prospective Seller</span>
                                @elseif ($activeConversation->tfc_grievance_id)
                                    <span class="badge bg-warning text-dark rounded-pill small" style="font-size: 0.65rem;">Grievance Complainant</span>
                                @endif
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-telephone-fill me-1"></i>+{{ $activeConversation->phone }}
                                &bull; <span class="badge {{ $activeConversation->status === 'resolved' ? 'bg-secondary' : 'bg-success' }} py-0 px-2" style="font-size: 0.65rem;">{{ ucfirst($activeConversation->status) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshMessages" title="Refresh messages">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#sendTemplateModal">
                            <i class="bi bi-file-earmark-code me-1"></i>Send Template
                        </button>
                    </div>
                </div>

                {{-- 24h Customer Service Window Notice --}}
                @php
                    $isWindowOpen = $activeConversation->isWindowOpen();
                @endphp
                @if ($isWindowOpen)
                    <div class="py-1 px-3 bg-success-subtle text-success border-bottom small d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-check-circle-fill me-1"></i>24-Hour Free Service Window Open</span>
                        <span class="fw-semibold">{{ $activeConversation->windowRemainingFormatted() }}</span>
                    </div>
                @else
                    <div class="py-2 px-3 bg-warning-subtle text-warning-emphasis border-bottom small d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-info-circle me-1"></i>24-Hour window expired. Use an approved template to message this customer.</span>
                        <button type="button" class="btn btn-xs btn-dark py-0 px-2" data-bs-toggle="modal" data-bs-target="#sendTemplateModal" style="font-size: 0.7rem;">Select Template</button>
                    </div>
                @endif

                {{-- Messages Canvas --}}
                <div class="flex-grow-1 p-3 overflow-auto" id="tfcMessageCanvas" style="background: #F8FAFC;">
                    <div class="text-center my-2">
                        <span class="badge bg-light text-muted border px-3 py-1 small">
                            <i class="bi bi-lock-fill me-1 text-warning"></i> End-to-end encrypted with Meta WhatsApp Cloud API
                        </span>
                    </div>

                    @foreach ($activeConversation->messages as $msg)
                        @php
                            $isOut = $msg->direction === 'outbound';
                        @endphp
                        <div class="d-flex mb-3 {{ $isOut ? 'justify-content-end' : 'justify-content-start' }}" id="msg-{{ $msg->id }}">
                            <div class="rounded-3 p-3 shadow-sm position-relative"
                                 style="max-width: 78%; {{ $isOut ? 'background: #0A1D37; color: #FFFFFF;' : 'background: #FFFFFF; color: #1E293B; border: 1px solid #E2E8F0;' }}">
                                
                                {{-- Media Rendering --}}
                                @if ($msg->message_type === 'image' && $msg->media_url)
                                    <div class="mb-2 rounded overflow-hidden">
                                        <img src="{{ $msg->media_url }}" alt="Attachment" class="img-fluid rounded" style="max-height: 240px; width: 100%; object-fit: cover;">
                                    </div>
                                @elseif ($msg->message_type === 'document' && $msg->media_url)
                                    <div class="p-2 rounded mb-2 d-flex align-items-center gap-2 {{ $isOut ? 'bg-white bg-opacity-10' : 'bg-light' }}">
                                        <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                                        <div class="text-truncate">
                                            <div class="small fw-semibold text-truncate">{{ $msg->media_filename ?: 'Document.pdf' }}</div>
                                            <a href="{{ $msg->media_url }}" target="_blank" class="small {{ $isOut ? 'text-warning' : 'text-primary' }} text-decoration-none">
                                                Download File <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                {{-- Text Body --}}
                                @if ($msg->body)
                                    <div class="small" style="white-space: pre-wrap; word-break: break-word;">{{ $msg->body }}</div>
                                @endif

                                {{-- Footer Metadata --}}
                                <div class="d-flex align-items-center justify-content-end gap-1 mt-1 pt-1 {{ $isOut ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.68rem;">
                                    <span>{{ $msg->created_at->format('h:i A') }}</span>
                                    @if ($isOut)
                                        @if ($msg->status === 'read')
                                            <i class="bi bi-check2-all text-info" title="Read"></i>
                                        @elseif ($msg->status === 'delivered')
                                            <i class="bi bi-check2-all" title="Delivered"></i>
                                        @elseif ($msg->status === 'sent')
                                            <i class="bi bi-check2" title="Sent"></i>
                                        @elseif ($msg->status === 'failed')
                                            <i class="bi bi-exclamation-circle-fill text-danger" title="{{ $msg->error_message ?: 'Failed' }}"></i>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Message Input Box --}}
                <div class="p-3 border-top bg-white">
                    {{-- Quick reply picker bar --}}
                    @if ($quickReplies->isNotEmpty())
                        <div class="d-flex align-items-center gap-1 mb-2 overflow-auto pb-1" style="scrollbar-width: thin;">
                            <span class="text-muted small me-1"><i class="bi bi-lightning-charge-fill text-warning"></i> Quick:</span>
                            @foreach ($quickReplies->take(5) as $qr)
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill text-nowrap py-0 px-2 quick-reply-btn"
                                        data-content="{{ $qr->content }}" style="font-size: 0.72rem;">
                                    /{{ $qr->shortcut }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <form id="tfcMessageForm" class="d-flex align-items-end gap-2">
                        <input type="file" id="tfcMediaInput" class="d-none" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <button type="button" class="btn btn-outline-secondary rounded-circle" id="btnAttach" title="Attach image or PDF" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <div class="flex-grow-1 position-relative">
                            <textarea id="tfcMessageText" class="form-control rounded-3" rows="1" placeholder="Type a message or use quick reply..." style="resize: none;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success rounded-circle shadow-sm" id="btnSend" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ============================================================== --}}
            {{-- RIGHT COLUMN: Contact Details & Direct Selling Drawer          --}}
            {{-- ============================================================== --}}
            <div class="col-12 col-md-3 col-lg-3 border-start d-flex flex-column h-100 bg-light p-3 overflow-auto">
                {{-- Contact Profile Card --}}
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white text-center">
                    <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                         style="width: 54px; height: 54px; font-size: 1.25rem; background: #0A1D37;">
                        {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                    </div>
                    <h6 class="fw-bold mb-1 text-dark">{{ $activeConversation->contact_name ?: 'Unnamed Contact' }}</h6>
                    <p class="text-muted small mb-2">+{{ $activeConversation->phone }}</p>

                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-xs btn-outline-secondary rounded-pill px-3">
                            <i class="bi bi-telephone me-1"></i>Call
                        </a>
                        <a href="https://wa.me/{{ $activeConversation->phone }}" target="_blank" class="btn btn-xs btn-outline-success rounded-pill px-3">
                            <i class="bi bi-whatsapp me-1"></i>WhatsApp
                        </a>
                    </div>
                </div>

                {{-- Linked Inquiry / Prospective Seller Information --}}
                @if ($activeConversation->tfcInquiry)
                    @php $inq = $activeConversation->tfcInquiry; @endphp
                    <div class="card border-primary border-opacity-25 shadow-sm rounded-3 p-3 mb-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary"><i class="bi bi-person-badge me-1"></i>Seller Applicant</span>
                            <a href="{{ route('admin.tfc.inquiries.show', $inq) }}" class="small text-decoration-none">View App &rarr;</a>
                        </div>
                        <div class="small">
                            <div class="text-muted mb-1">Status: <strong class="text-dark">{{ ucfirst($inq->status) }}</strong></div>
                            @if ($inq->state)
                                <div class="text-muted mb-1">State: <strong class="text-dark">{{ $inq->state }}</strong></div>
                            @endif
                            @if ($inq->city)
                                <div class="text-muted mb-1">City: <strong class="text-dark">{{ $inq->city }}</strong></div>
                            @endif
                            @if ($inq->experience_years)
                                <div class="text-muted mb-1">Experience: <strong class="text-dark">{{ $inq->experience_years }} Years</strong></div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Linked Grievance Ticket --}}
                @if ($activeConversation->tfcGrievance)
                    @php $grv = $activeConversation->tfcGrievance; @endphp
                    <div class="card border-warning border-opacity-50 shadow-sm rounded-3 p-3 mb-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-warning text-dark"><i class="bi bi-shield-exclamation me-1"></i>Rule 7 Grievance</span>
                            <a href="{{ route('admin.tfc.grievances.show', $grv) }}" class="small text-decoration-none">Ticket &rarr;</a>
                        </div>
                        <div class="small">
                            <div class="text-muted mb-1">Ticket #: <strong class="text-dark">{{ $grv->ticket_number }}</strong></div>
                            <div class="text-muted mb-1">Status: <strong class="text-dark">{{ ucfirst($grv->status) }}</strong></div>
                            @if ($grv->product_name)
                                <div class="text-muted mb-1">Product: <strong class="text-dark">{{ $grv->product_name }}</strong></div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Internal Agent Notes --}}
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
                    <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-sticky me-1 text-warning"></i>Internal Notes</h6>
                    <form id="tfcNotesForm">
                        <textarea id="tfcNotesText" class="form-control form-control-sm mb-2" rows="3" placeholder="Add confidential notes for this contact...">{{ $activeConversation->notes }}</textarea>
                        <button type="submit" class="btn btn-sm btn-dark w-100" id="btnSaveNote">Save Notes</button>
                    </form>
                </div>

                {{-- Conversation Management --}}
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                    <h6 class="fw-bold small text-dark mb-2">Manage Conversation</h6>
                    
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Assigned Agent</label>
                        <select id="tfcAssignAgent" class="form-select form-select-sm">
                            <option value="">Unassigned</option>
                            @foreach ($admins as $adm)
                                <option value="{{ $adm->id }}" {{ $activeConversation->assigned_to == $adm->id ? 'selected' : '' }}>
                                    {{ $adm->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select id="tfcUpdateStatus" class="form-select form-select-sm">
                            <option value="active" {{ $activeConversation->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="resolved" {{ $activeConversation->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $activeConversation->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            <option value="spam" {{ $activeConversation->status === 'spam' ? 'selected' : '' }}>Spam</option>
                        </select>
                    </div>
                </div>
            </div>
        @else
            {{-- Blank Canvas when no conversation selected --}}
            <div class="col-12 col-md-8 col-lg-9 d-flex flex-column align-items-center justify-content-center h-100 bg-light p-4 text-center">
                <div class="rounded-circle bg-white shadow-sm p-4 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-whatsapp display-5 text-success"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Select a TFC WhatsApp Conversation</h4>
                <p class="text-muted small" style="max-width: 460px;">
                    Pick an active prospective seller inquiry, grievance complainant, or direct seller from the left list to start real-time messaging.
                </p>
                <button type="button" class="btn btn-dark btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <i class="bi bi-chat-plus me-1"></i>Start New Conversation
                </button>
            </div>
        @endif
    </div>
</div>

{{-- ====================================================================== --}}
{{-- MODALS: Start New Chat & Send Template                                 --}}
{{-- ====================================================================== --}}

{{-- Start New Chat Modal --}}
<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="GET" action="{{ route('admin.tfc.whatsapp.inbox') }}" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-whatsapp text-success me-2"></i>Initiate WhatsApp Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Mobile Number (with country code)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">+</span>
                        <input type="text" name="phone" class="form-control" placeholder="919876543210" required>
                    </div>
                    <div class="form-text small">E.g., 919876543210 (India country code 91 + 10-digit number)</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Contact / Seller Name (Optional)</label>
                    <input type="text" name="name" class="form-control" placeholder="Rajesh Kumar">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm px-4">Open Conversation</button>
            </div>
        </form>
    </div>
</div>

{{-- Send Template Modal --}}
@if ($activeConversation)
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="tfcSendTemplateForm" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="bi bi-file-earmark-code text-primary me-2"></i>Send Approved Meta WhatsApp Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Select Approved Template</label>
                    <select id="templateSelector" name="template_name" class="form-select" required>
                        <option value="">-- Choose a Meta Approved Template --</option>
                        @foreach ($approvedTemplates as $tmpl)
                            <option value="{{ $tmpl->name }}" data-body="{{ $tmpl->body_text }}" data-lang="{{ $tmpl->language }}" data-vars="{{ $tmpl->variable_count }}">
                                {{ $tmpl->name }} ({{ strtoupper($tmpl->category) }} - {{ $tmpl->language }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Template Preview Card --}}
                <div class="card border rounded-3 p-3 mb-3 bg-light" id="templatePreviewCard" style="display: none;">
                    <div class="fw-semibold small text-muted mb-1">Template Content Preview:</div>
                    <div id="templatePreviewBody" class="small text-dark" style="white-space: pre-wrap;"></div>
                </div>

                {{-- Dynamic Variables Container --}}
                <div id="templateVariablesContainer" class="mb-3" style="display: none;">
                    <label class="form-label small fw-semibold text-dark">Template Variables (Fill in dynamic parameters)</label>
                    <div id="templateVariablesInputs" class="row g-2"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4" id="btnSubmitTemplate">Send Template</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if ($activeConversation)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const conversationId = {{ $activeConversation->id }};
    const messageCanvas = document.getElementById('tfcMessageCanvas');
    const messageForm = document.getElementById('tfcMessageForm');
    const messageText = document.getElementById('tfcMessageText');
    const mediaInput = document.getElementById('tfcMediaInput');
    const btnAttach = document.getElementById('btnAttach');
    const btnSend = document.getElementById('btnSend');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Scroll chat canvas to bottom on load
    if (messageCanvas) {
        messageCanvas.scrollTop = messageCanvas.scrollHeight;
    }

    // Quick replies insertion
    document.querySelectorAll('.quick-reply-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            messageText.value = this.dataset.content;
            messageText.focus();
        });
    });

    // Send Text Message
    messageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const text = messageText.value.trim();
        if (!text) return;

        btnSend.disabled = true;
        btnSend.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch("{{ route('admin.tfc.whatsapp.inbox.send', $activeConversation) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="bi bi-send-fill"></i>';

            if (data.success) {
                messageText.value = '';
                appendMessage(data.message, true);
            } else {
                alert('Send failed: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(err => {
            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="bi bi-send-fill"></i>';
            alert('Network error sending message.');
        });
    });

    // Media Attachment
    btnAttach.addEventListener('click', () => mediaInput.click());

    mediaInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;
        const file = this.files[0];
        const caption = prompt('Add an optional caption for this file:', '') || '';

        const formData = new FormData();
        formData.append('file', file);
        if (caption) formData.append('caption', caption);

        btnAttach.disabled = true;

        fetch("{{ route('admin.tfc.whatsapp.inbox.send-media', $activeConversation) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnAttach.disabled = false;
            mediaInput.value = '';
            if (data.success) {
                appendMessage(data.message, true);
            } else {
                alert('Media upload failed: ' + (data.message || 'Error'));
            }
        })
        .catch(() => {
            btnAttach.disabled = false;
            mediaInput.value = '';
            alert('Network error uploading media.');
        });
    });

    // Send Template Handling
    const templateSelector = document.getElementById('templateSelector');
    const templatePreviewCard = document.getElementById('templatePreviewCard');
    const templatePreviewBody = document.getElementById('templatePreviewBody');
    const templateVarsContainer = document.getElementById('templateVariablesContainer');
    const templateVarsInputs = document.getElementById('templateVariablesInputs');
    const sendTemplateForm = document.getElementById('tfcSendTemplateForm');

    if (templateSelector) {
        templateSelector.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt.value) {
                templatePreviewCard.style.display = 'none';
                templateVarsContainer.style.display = 'none';
                return;
            }

            templatePreviewBody.textContent = opt.dataset.body || '';
            templatePreviewCard.style.display = 'block';

            const varCount = parseInt(opt.dataset.vars || '0', 10);
            templateVarsInputs.innerHTML = '';
            if (varCount > 0) {
                templateVarsContainer.style.display = 'block';
                for (let i = 1; i <= varCount; i++) {
                    const col = document.createElement('div');
                    col.className = 'col-12 col-md-6';
                    col.innerHTML = `
                        <label class="form-label small text-muted mb-0">Parameter {{` + i + `}}</label>
                        <input type="text" name="parameters[]" class="form-control form-control-sm" required placeholder="Value for {{` + i + `}}">
                    `;
                    templateVarsInputs.appendChild(col);
                }
            } else {
                templateVarsContainer.style.display = 'none';
            }
        });
    }

    if (sendTemplateForm) {
        sendTemplateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const tmplName = templateSelector.value;
            const opt = templateSelector.options[templateSelector.selectedIndex];
            const lang = opt ? opt.dataset.lang : 'en';

            const params = [];
            sendTemplateForm.querySelectorAll('input[name="parameters[]"]').forEach(inp => {
                params.push(inp.value);
            });

            const btnSubmit = document.getElementById('btnSubmitTemplate');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Sending...';

            fetch("{{ route('admin.tfc.whatsapp.inbox.send-template', $activeConversation) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    template_name: tmplName,
                    language: lang,
                    parameters: params
                })
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Send Template';
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('sendTemplateModal')).hide();
                    appendMessage(data.message, true);
                } else {
                    alert('Template send failed: ' + (data.message || 'Error'));
                }
            })
            .catch(() => {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Send Template';
                alert('Network error sending template.');
            });
        });
    }

    // Save Notes Form
    const notesForm = document.getElementById('tfcNotesForm');
    if (notesForm) {
        notesForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const notes = document.getElementById('tfcNotesText').value;
            fetch("{{ route('admin.tfc.whatsapp.inbox.note', $activeConversation) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Notes saved successfully.');
                }
            });
        });
    }

    // Assign Agent
    const selectAgent = document.getElementById('tfcAssignAgent');
    if (selectAgent) {
        selectAgent.addEventListener('change', function () {
            fetch("{{ route('admin.tfc.whatsapp.inbox.assign', $activeConversation) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ assigned_to: this.value })
            });
        });
    }

    // Update Status
    const selectStatus = document.getElementById('tfcUpdateStatus');
    if (selectStatus) {
        selectStatus.addEventListener('change', function () {
            fetch("{{ route('admin.tfc.whatsapp.inbox.status', $activeConversation) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: this.value })
            });
        });
    }

    // Live Polling every 4 seconds
    let lastMessageId = {{ $activeConversation->messages->last()?->id ?? 0 }};

    function pollNewMessages() {
        fetch("{{ route('admin.tfc.whatsapp.inbox.poll', $activeConversation) }}?after_id=" + lastMessageId)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg, msg.direction === 'outbound');
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                }
            })
            .catch(() => {});
    }

    setInterval(pollNewMessages, 4000);

    // Helper: Append message to canvas
    function appendMessage(msg, isOut) {
        if (document.getElementById('msg-' + msg.id)) return;

        const div = document.createElement('div');
        div.id = 'msg-' + msg.id;
        div.className = 'd-flex mb-3 ' + (isOut ? 'justify-content-end' : 'justify-content-start');
        
        let mediaHtml = '';
        if (msg.message_type === 'image' && msg.media_url) {
            mediaHtml = '<div class="mb-2 rounded overflow-hidden"><img src="' + msg.media_url + '" class="img-fluid rounded" style="max-height: 240px; width: 100%; object-fit: cover;"></div>';
        } else if (msg.message_type === 'document' && msg.media_url) {
            mediaHtml = '<div class="p-2 rounded mb-2 d-flex align-items-center gap-2 ' + (isOut ? 'bg-white bg-opacity-10' : 'bg-light') + '"><i class="bi bi-file-earmark-pdf fs-3 text-danger"></i><div class="text-truncate"><div class="small fw-semibold text-truncate">' + (msg.media_filename || 'Document.pdf') + '</div><a href="' + msg.media_url + '" target="_blank" class="small ' + (isOut ? 'text-warning' : 'text-primary') + ' text-decoration-none">Download File</a></div></div>';
        }

        const bodyHtml = msg.body ? '<div class="small" style="white-space: pre-wrap; word-break: break-word;">' + escapeHtml(msg.body) + '</div>' : '';
        const bgStyle = isOut ? 'background: #0A1D37; color: #FFFFFF;' : 'background: #FFFFFF; color: #1E293B; border: 1px solid #E2E8F0;';

        div.innerHTML = `
            <div class="rounded-3 p-3 shadow-sm position-relative" style="max-width: 78%; ` + bgStyle + `">
                ` + mediaHtml + `
                ` + bodyHtml + `
                <div class="d-flex align-items-center justify-content-end gap-1 mt-1 pt-1 ` + (isOut ? 'text-white-50' : 'text-muted') + `" style="font-size: 0.68rem;">
                    <span>Just now</span>
                    ` + (isOut ? '<i class="bi bi-check2"></i>' : '') + `
                </div>
            </div>
        `;

        messageCanvas.appendChild(div);
        messageCanvas.scrollTop = messageCanvas.scrollHeight;
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
@endif
@endpush
