@extends('admin.layouts.app')

@section('title', 'WhatsApp Inbox & Live Chat')

@push('styles')
<style>
.whatsapp-inbox-wrapper {
    height: calc(100vh - 110px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.wa-chat-card {
    flex: 1 1 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #ffffff;
}
.wa-chat-card > .row {
    flex: 1 1 0;
    min-height: 0;
    height: 100%;
    overflow: hidden;
}
.wa-sidebar-column {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.wa-chat-column {
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.wa-context-column {
    height: 100%;
    min-height: 0;
    overflow-y: auto;
}
.wa-convo-list {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
}
.wa-chat-stream {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    background-color: #f6f8f7;
    background-image: radial-gradient(#e2e8e5 1px, transparent 1px);
    background-size: 20px 20px;
    scroll-behavior: smooth;
}
.wa-avatar {
    width: 38px;
    height: 38px;
    font-size: 0.95rem;
}
.wa-convo-item {
    cursor: pointer;
    background: #ffffff;
    transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}
.wa-convo-item:hover {
    background: #f8faf9;
}
.wa-convo-item.active {
    background: #e8f5ec;
    border-left: 3px solid #25D366 !important;
}
.wa-bubble {
    max-width: 78%;
    border-radius: 14px;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    word-break: break-word;
}
.wa-bubble-inbound {
    background-color: #ffffff;
    border-bottom-left-radius: 3px;
    color: #1e293b;
    border: 1px solid rgba(0,0,0,0.04);
}
.wa-bubble-outbound {
    background-color: #dcf8c6;
    border-bottom-right-radius: 3px;
    color: #0f381e;
}
.btn-xs {
    padding: 0.2rem 0.65rem;
    font-size: 0.72rem;
}
.btn-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.wa-filter-pills::-webkit-scrollbar {
    height: 4px;
}
.wa-filter-pills::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
.rotate-spin {
    animation: waSpin 0.75s linear infinite;
}
@keyframes waSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endpush

@section('content')
<div class="whatsapp-inbox-wrapper">
    {{-- Top Operational Toolbar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2 flex-shrink-0">
        <div class="d-flex align-items-center gap-3">
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-whatsapp text-success fs-3"></i>
                <span>WhatsApp Inbox</span>
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span id="waTotalUnreadBadge" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1 {{ $totalUnread > 0 ? '' : 'd-none' }}">
                    <i class="bi bi-chat-dots-fill me-1"></i><span id="waTotalUnreadCount">{{ $totalUnread }}</span> unread
                </span>
                <span id="waActiveWindowsBadge" class="badge rounded-pill bg-emerald-subtle text-emerald border border-emerald-subtle px-2.5 py-1">
                    <i class="bi bi-clock-history me-1"></i><span id="waActiveWindowsCount">{{ $activeWindowsCount }}</span> active windows (24h)
                </span>
                <span id="waRealtimeSyncBadge" class="badge bg-light text-muted border px-2 py-1 rounded-pill small d-none d-md-inline-flex align-items-center gap-1" title="Real-time Auto-Sync Active">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 6px; height: 6px;"></span>
                    Live
                </span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($isConfigured)
                <span class="badge bg-success-subtle text-success border border-success px-3 py-1.5 rounded-pill d-inline-flex align-items-center gap-1.5">
                    <i class="bi bi-shield-check"></i>
                    Meta Cloud API Connected
                </span>
            @else
                <a href="{{ route('admin.settings.index') }}#whatsapp" class="badge bg-warning-subtle text-warning border border-warning px-3 py-1.5 rounded-pill text-decoration-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>API Setup Required
                </a>
            @endif
        </div>
    </div>

    {{-- Main 3-Column Chat Grid --}}
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden wa-chat-card">
        <div class="row g-0 h-100">

            {{-- 1. Left Column: Conversation Directory --}}
            <div class="col-12 col-md-4 col-xl-3 border-end d-flex flex-column wa-sidebar-column {{ $activeConversation ? 'd-none d-md-flex' : '' }}" id="waSidebarColumn">
                {{-- Search & Filters --}}
                <div class="p-3 border-bottom bg-light-subtle flex-shrink-0">
                    <form method="GET" action="{{ route('admin.whatsapp.inbox') }}" class="mb-2.5" id="waSearchForm">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="waSearchInput" value="{{ $search }}" class="form-control bg-white border-start-0 ps-0"
                                   placeholder="Search name, phone, message...">
                            @if ($search)
                                <a href="{{ route('admin.whatsapp.inbox', ['filter' => $filter]) }}" class="input-group-text bg-white text-muted text-decoration-none">&times;</a>
                            @endif
                        </div>
                    </form>

                    {{-- Filter Tabs --}}
                    <div class="d-flex gap-1 overflow-x-auto pb-1 wa-filter-pills">
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'all']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">
                            All
                        </a>
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'mine']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'mine' ? 'btn-dark' : 'btn-light text-muted' }}">
                            Mine
                        </a>
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'unassigned']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'unassigned' ? 'btn-dark' : 'btn-light text-muted' }}">
                            Unassigned
                        </a>
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'unread']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'unread' ? 'btn-dark' : 'btn-light text-muted' }}">
                            Unread @if($totalUnread > 0)({{ $totalUnread }})@endif
                        </a>
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'window_open']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'window_open' ? 'btn-dark' : 'btn-light text-muted' }}">
                            Open (24h)
                        </a>
                        <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'resolved']) }}"
                           class="btn btn-xs rounded-pill {{ $filter === 'resolved' ? 'btn-dark' : 'btn-light text-muted' }}">
                            Resolved
                        </a>
                    </div>
                </div>

                {{-- Conversations List --}}
                <div class="flex-grow-1 overflow-y-auto wa-convo-list" id="waConvoList">
                    @forelse ($conversations as $convo)
                        @php
                            $isActive = $activeConversation && $activeConversation->id === $convo->id;
                            $hasWindow = $convo->isWindowOpen();
                        @endphp
                        <a href="{{ route('admin.whatsapp.inbox', ['conversation_id' => $convo->id, 'filter' => $filter, 'search' => $search]) }}"
                           class="wa-convo-item d-block p-3 border-bottom text-decoration-none transition-all {{ $isActive ? 'active' : '' }}"
                           id="convo-item-{{ $convo->id }}"
                           data-convo-id="{{ $convo->id }}">
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="position-relative flex-shrink-0">
                                    <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="background-color: {{ '#' . substr(md5($convo->phone), 0, 6) }};">
                                        {{ strtoupper(substr($convo->contact_name ?: $convo->phone, 0, 1)) }}
                                    </div>
                                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1 wa-convo-window-dot {{ $hasWindow ? '' : 'd-none' }}"
                                          title="24h Service Window Active"></span>
                                </div>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center justify-content-between mb-0.5">
                                        <span class="fw-semibold text-dark text-truncate d-block small wa-convo-name" style="max-width: 140px;">
                                            {{ $convo->contact_name ?: '+' . $convo->phone }}
                                        </span>
                                        <span class="text-muted wa-convo-time" style="font-size: 0.7rem;">
                                            {{ $convo->last_message_at ? $convo->last_message_at->shortRelativeDiffForHumans() : '' }}
                                        </span>
                                    </div>

                                    <div class="text-muted small text-truncate mb-1 wa-convo-preview" style="font-size: 0.78rem;">
                                        {{ $convo->last_message_preview ?: 'No messages yet' }}
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge wa-convo-window-badge {{ $hasWindow ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}" style="font-size: 0.65rem;">
                                                {{ $convo->windowRemainingFormatted() }}
                                            </span>
                                            @if ($convo->assigned_to)
                                                <span class="badge bg-light text-primary border" style="font-size: 0.62rem;" title="Assigned Agent">
                                                    <i class="bi bi-person"></i> {{ \Illuminate\Support\Str::limit($convo->assignedAgent?->name ?? 'Agent', 10) }}
                                                </span>
                                            @endif
                                            @if ($convo->status === 'resolved')
                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.62rem;">
                                                    Resolved
                                                </span>
                                            @endif
                                        </div>

                                        <span class="badge rounded-pill bg-success text-white px-2 py-0.5 wa-convo-unread-pill {{ $convo->unread_count > 0 ? '' : 'd-none' }}" style="font-size: 0.7rem;">
                                            {{ $convo->unread_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-5 text-center text-muted" id="waEmptyConvoNotice">
                            <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-muted opacity-50"></i>
                            <div class="fw-semibold small">No conversations found</div>
                            <p class="small text-muted mb-0">When customers message your WhatsApp number, they will appear here instantly.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination Links --}}
                @if ($conversations->hasPages())
                    <div class="p-2 border-top bg-light-subtle d-flex justify-content-center flex-shrink-0">
                        {{ $conversations->links('pagination::simple-bootstrap-5') }}
                    </div>
                @endif
            </div>

            {{-- 2. Middle Column: Active Chat Stream & Composer --}}
            @if ($activeConversation)
                <div class="col-12 col-md-8 col-xl-6 d-flex flex-column wa-chat-column bg-white">
                    {{-- Chat Topbar --}}
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-white z-1 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2 gap-md-3">
                            <a href="{{ route('admin.whatsapp.inbox', ['filter' => $filter, 'search' => $search]) }}" class="btn btn-sm btn-light border rounded-circle btn-icon d-md-none me-1" title="Back to conversations">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                            <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                 style="background-color: {{ '#' . substr(md5($activeConversation->phone), 0, 6) }}; width: 42px; height: 42px; font-size: 1.05rem;">
                                {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }}</h6>
                                <div class="d-flex align-items-center gap-2 mt-0.5">
                                    <span class="text-muted small" style="font-size: 0.78rem;">
                                        <i class="bi bi-telephone text-muted me-1"></i>+{{ $activeConversation->phone }}
                                    </span>
                                    <span class="badge {{ $activeConversation->isWindowOpen() ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }}"
                                          style="font-size: 0.7rem;" id="activeWindowBadge">
                                        <i class="bi {{ $activeConversation->isWindowOpen() ? 'bi-unlock-fill' : 'bi-lock-fill' }} me-1"></i>
                                        <span id="activeWindowLabel">{{ $activeConversation->windowRemainingFormatted() }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1.5 gap-md-2">
                            {{-- Agent Assignment Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 d-flex align-items-center gap-1.5" type="button" data-bs-toggle="dropdown" id="btnAssignDropdown" title="Assign to team agent">
                                    <i class="bi bi-person text-secondary"></i>
                                    <span id="assignedAgentLabel" class="small fw-semibold text-dark">
                                        {{ $activeConversation->assignedAgent?->name ?? 'Unassigned' }}
                                    </span>
                                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.65rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-1" style="font-size: 0.8rem; min-width: 180px;">
                                    <li class="dropdown-header text-uppercase fw-bold text-muted py-1" style="font-size: 0.68rem;">Assign To Agent</li>
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-1.5 assign-agent-btn fw-medium text-primary" data-admin-id="{{ auth('admin')->id() }}">
                                            <i class="bi bi-person-check me-1.5"></i> Assign to Me
                                        </button>
                                    </li>
                                    @foreach ($admins as $adm)
                                        @if ($adm->id !== auth('admin')->id())
                                            <li>
                                                <button type="button" class="dropdown-item rounded-2 py-1.5 assign-agent-btn" data-admin-id="{{ $adm->id }}">
                                                    <i class="bi bi-person me-1.5"></i> {{ $adm->name }}
                                                </button>
                                            </li>
                                        @endif
                                    @endforeach
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-1.5 assign-agent-btn text-danger" data-admin-id="">
                                            <i class="bi bi-person-x me-1.5"></i> Unassign
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            {{-- Conversation Lifecycle Status --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 d-flex align-items-center gap-1.5" type="button" data-bs-toggle="dropdown" id="btnStatusDropdown" title="Conversation Lifecycle Status">
                                    @php
                                        $statusConfig = match($activeConversation->status) {
                                            'resolved' => ['color' => 'secondary', 'icon' => 'bi-check2-all', 'label' => 'Resolved'],
                                            'snoozed' => ['color' => 'warning', 'icon' => 'bi-alarm', 'label' => 'Snoozed'],
                                            default => ['color' => 'success', 'icon' => 'bi-circle-fill', 'label' => 'Open']
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusConfig['color'] }}-subtle text-{{ $statusConfig['color'] }} p-1 rounded-circle">
                                        <i class="bi {{ $statusConfig['icon'] }}" style="font-size: 0.65rem;"></i>
                                    </span>
                                    <span id="convoStatusLabel" class="small fw-semibold text-dark">{{ $statusConfig['label'] }}</span>
                                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.65rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 p-1" style="font-size: 0.8rem;">
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-1.5 change-status-btn text-success fw-medium" data-status="active">
                                            <i class="bi bi-check2 me-1.5"></i> Mark Open / Active
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-1.5 change-status-btn text-secondary" data-status="resolved">
                                            <i class="bi bi-check-all me-1.5"></i> Mark as Resolved
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-1.5 change-status-btn text-warning" data-status="snoozed">
                                            <i class="bi bi-alarm me-1.5"></i> Snooze Chat
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-light-sm btn-icon rounded-circle" id="btnRefreshChat" title="Refresh messages">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button class="btn btn-light-sm d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#customerContextOffcanvas">
                                <i class="bi bi-person-badge me-1"></i>Profile
                            </button>
                        </div>
                    </div>

                    {{-- Window Closed Notice (Meta Policy) --}}
                    <div id="waWindowExpiredNotice" class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-3 small d-flex align-items-center justify-content-between flex-shrink-0 {{ $activeConversation->isWindowOpen() ? 'd-none' : '' }}">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                            <span><strong>24-Hour window expired.</strong> Free-form text may fail. Send an approved Meta Template to resume.</span>
                        </div>
                        <button type="button" class="btn btn-warning btn-xs rounded-pill px-2.5 py-1" data-bs-toggle="modal" data-bs-target="#sendTemplateModal">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Send Template
                        </button>
                    </div>

                    {{-- Scrollable Messages Stream --}}
                    <div class="flex-grow-1 p-3 overflow-y-auto wa-chat-stream d-flex flex-column gap-3" id="waChatStream">
                        @forelse ($messages as $msg)
                            @include('admin.whatsapp.partials.message-bubble', ['msg' => $msg])
                        @empty
                            <div class="my-auto text-center text-muted p-5" id="waEmptyMessageNotice">
                                <i class="bi bi-chat-heart fs-1 d-block mb-2 text-muted opacity-50"></i>
                                <div class="fw-semibold">No messages yet with this customer</div>
                                <p class="small text-muted">Type below to start the conversation.</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Attachment Preview Chip (hidden by default) --}}
                    <div id="waAttachmentPreview" class="p-2 border-top bg-light-subtle d-none d-flex align-items-center justify-content-between flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            <div id="waAttachmentThumb" class="rounded overflow-hidden" style="width: 36px; height: 36px; display: none;">
                                <img src="" alt="preview" class="w-100 h-100 object-fit-cover">
                            </div>
                            <i class="bi bi-paperclip fs-5 text-muted" id="waAttachmentGenericIcon"></i>
                            <div>
                                <div class="fw-semibold small text-truncate" id="waAttachmentName" style="max-width: 250px;">filename.pdf</div>
                                <div class="text-muted" style="font-size: 0.7rem;" id="waAttachmentSize">1.2 MB</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light rounded-circle text-danger p-1" id="waRemoveAttachment">
                            <i class="bi bi-x fs-5"></i>
                        </button>
                    </div>

                    {{-- Bottom Chat Composer --}}
                    <div class="p-3 border-top bg-white transition-all flex-shrink-0" id="waComposerWrapper">
                        {{-- Mode Selector: Public WhatsApp Reply vs Private Staff Note --}}
                        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                            <div class="btn-group btn-group-sm rounded-pill p-0.5 bg-light" role="group">
                                <button type="button" class="btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode active bg-white text-dark shadow-xs" id="btnModeReply">
                                    <i class="bi bi-whatsapp me-1 text-success"></i> Customer Reply
                                </button>
                                <button type="button" class="btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode text-muted" id="btnModeNote">
                                    <i class="bi bi-lock-fill me-1 text-warning"></i> Internal Note
                                </button>
                            </div>
                            <span class="small text-muted" style="font-size: 0.72rem;" id="modeHintText">
                                <i class="bi bi-info-circle me-1"></i>Sends message directly to customer's WhatsApp
                            </span>
                        </div>
                        <form id="waSendForm" class="d-flex align-items-end gap-2">
                            @csrf
                            {{-- Hidden file input --}}
                            <input type="file" id="waFileInput" class="d-none" accept="image/*,application/pdf,.doc,.docx,audio/*,video/*">

                            <button type="button" class="btn btn-light rounded-circle btn-icon p-2.5 flex-shrink-0" id="btnAttachFile" title="Attach image or document">
                                <i class="bi bi-paperclip fs-5 text-muted"></i>
                            </button>

                            <div class="dropdown">
                                <button type="button" class="btn btn-light rounded-circle btn-icon p-2.5 flex-shrink-0 text-primary" data-bs-toggle="dropdown" aria-expanded="false" id="btnQuickReplies" title="Quick Reply Snippets (or type / in message)">
                                    <i class="bi bi-chat-square-quote-fill fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2 wa-quick-reply-dropdown" style="max-height: 280px; overflow-y: auto; width: 320px; z-index: 1060;" id="waQuickReplyMenu">
                                    <li class="dropdown-header small text-uppercase fw-bold text-muted px-2 py-1">Quick Reply Snippets</li>
                                    <li class="text-center py-2 text-muted small"><span class="spinner-border spinner-border-sm"></span> Loading snippets...</li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-light rounded-circle btn-icon p-2.5 flex-shrink-0 text-warning" data-bs-toggle="modal" data-bs-target="#sendTemplateModal" title="Send Meta Template (Bypasses 24h Window)">
                                <i class="bi bi-lightning-charge-fill fs-5"></i>
                            </button>

                            <div class="flex-grow-1 position-relative">
                                {{-- Inline Slash Suggestions --}}
                                <div id="waSlashSuggestions" class="position-absolute bottom-100 start-0 mb-2 w-100 bg-white rounded-3 shadow-lg border p-1 d-none" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                                </div>
                                <textarea id="waMessageInput" rows="1" class="form-control rounded-4 pe-4 py-2.5"
                                          placeholder="Type a message or type / for quick replies..."
                                          style="resize: none; max-height: 120px; overflow-y: auto;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-success rounded-circle btn-icon p-2.5 flex-shrink-0 shadow-sm" id="btnSendMessage" title="Send message">
                                <i class="bi bi-send-fill text-white fs-6"></i>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- 3. Right Column: Customer Context Drawer --}}
                <div class="col-12 col-xl-3 border-start d-none d-xl-flex flex-column wa-context-column bg-light-subtle overflow-y-auto">
                    @include('admin.whatsapp.partials.customer-context', ['customerContext' => $customerContext, 'activeConversation' => $activeConversation])
                </div>

                {{-- Offcanvas Drawer for mobile/tablet --}}
                <div class="offcanvas offcanvas-end" tabindex="-1" id="customerContextOffcanvas" aria-labelledby="customerContextOffcanvasLabel">
                    <div class="offcanvas-header border-bottom">
                        <h5 class="offcanvas-title fw-bold" id="customerContextOffcanvasLabel">Customer Context</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body p-0">
                        @include('admin.whatsapp.partials.customer-context', ['customerContext' => $customerContext, 'activeConversation' => $activeConversation])
                    </div>
                </div>

            @else
                <div class="col-12 col-md-8 col-xl-9 d-flex flex-column align-items-center justify-content-center p-5 text-center bg-white" style="min-height: 500px;">
                    <div class="wa-empty-illustration mb-3 p-4 rounded-circle bg-emerald-subtle text-emerald" style="width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-whatsapp fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Conversation Selected</h5>
                    <p class="text-muted small" style="max-width: 360px;">Select a conversation from the left to read messages, send replies, or share attachments directly with customers.</p>
                </div>
            @endif

        </div>
    </div>
</div>

@if ($activeConversation)
<!-- Send Meta Template Modal -->
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-labelledby="sendTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header bg-emerald text-white p-3 px-4" style="background-color: #1f3b25;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="sendTemplateModalLabel">Send Approved Meta Template</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Bypasses 24-hour service window & initiates customer conversation</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="waSendTemplateForm">
                <div class="modal-body p-4 bg-light-subtle">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark">Choose Meta Template</label>
                                <select class="form-select rounded-3" id="tmplSelect" required>
                                    @if(isset($approvedTemplates) && $approvedTemplates->isNotEmpty())
                                        <optgroup label="Synchronized Meta Templates">
                                            @foreach($approvedTemplates as $at)
                                                <option value="{{ $at->name }}" data-category="{{ $at->category }}">{{ $at->name }} ({{ ucfirst($at->category) }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    <optgroup label="Standard Presets">
                                        <option value="order_confirmation">📦 Order Confirmation (3 variables)</option>
                                        <option value="abandoned_cart_recovery">🛒 Abandoned Cart Recovery (2 variables)</option>
                                        <option value="vanriti_welcome">🌿 Welcome Offer (1 variable)</option>
                                        <option value="promotional_offer">🎉 Promotional Discount (3 variables)</option>
                                        <option value="custom">⚙️ Custom Approved Template Name</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="customTmplNameGroup">
                                <label class="form-label fw-bold small text-dark">Custom Template Name (Exact Meta Name)</label>
                                <input type="text" class="form-control rounded-3" id="customTmplName" placeholder="e.g. festive_vip_discount">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark">Language Code</label>
                                <input type="text" class="form-control rounded-3" id="tmplLangCode" value="en" required>
                            </div>

                            <div id="tmplDynamicFields">
                                <!-- Dynamically populated parameter fields -->
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark">Header Image URL (Optional)</label>
                                <input type="url" class="form-control rounded-3" id="tmplHeaderImageUrl" placeholder="https://vanriti.com/images/banner.jpg">
                                <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">Only needed if your template requires a Media Image Header.</small>
                            </div>
                        </div>

                        <!-- Live WhatsApp Phone Preview -->
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold small text-muted text-uppercase" style="font-size: 0.7rem;">Live WhatsApp Preview</label>
                            <div class="p-3 rounded-4 bg-white border shadow-xs d-flex flex-column" style="min-height: 280px; background-color: #f7f9f8; background-image: radial-gradient(#e0e7e4 1px, transparent 1px); background-size: 15px 15px;">
                                <div id="previewHeaderImage" class="rounded-3 overflow-hidden mb-2 d-none" style="max-height: 140px;">
                                    <img src="" class="w-100 h-100 object-fit-cover" alt="header">
                                </div>
                                <div class="wa-bubble wa-bubble-outbound p-3 w-100 shadow-sm" style="max-width: 100%;">
                                    <div class="fw-bold small text-success mb-1" id="previewTitle">VANRITI</div>
                                    <div class="small text-dark" id="previewBodyText">Loading template preview...</div>
                                    <div class="text-end text-muted mt-2" style="font-size: 0.65rem;">Now • <i class="bi bi-check2 text-muted"></i></div>
                                </div>
                                <div class="mt-auto pt-3 text-center text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-shield-check text-success me-1"></i>Pre-approved Meta Template
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3 px-4 bg-white border-top">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold" id="btnSubmitTemplate">
                        <i class="bi bi-send-fill me-1"></i>Send Template Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = '{{ csrf_token() }}';
    const pollConversationsUrl = '{{ route('admin.whatsapp.inbox.poll-conversations') }}';
    const currentSearch = '{{ addslashes($search) }}';
    const currentFilter = '{{ addslashes($filter) }}';
    const activeConvoId = {{ $activeConversation ? $activeConversation->id : 'null' }};
    const waConvoList = document.getElementById('waConvoList');
    const waTotalUnreadBadge = document.getElementById('waTotalUnreadBadge');
    const waTotalUnreadCount = document.getElementById('waTotalUnreadCount');
    const waActiveWindowsCount = document.getElementById('waActiveWindowsCount');

    let prevTotalUnread = {{ $totalUnread }};
    let isDirectoryPolling = false;

    // Subtle Web Audio Chime when incoming message arrives
    function playNotificationChime() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) {
            // Audio context policy ignored
        }
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // -------------------------------------------------------------
    // Real-Time Global Directory Polling (Sidebar & Badges Sync)
    // -------------------------------------------------------------
    async function pollDirectory() {
        if (isDirectoryPolling) return;
        isDirectoryPolling = true;

        try {
            const url = `${pollConversationsUrl}?search=${encodeURIComponent(currentSearch)}&filter=${encodeURIComponent(currentFilter)}`;
            const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;

            const data = await res.json();

            // 1. Update toolbar counters
            if (waTotalUnreadBadge && waTotalUnreadCount) {
                waTotalUnreadCount.textContent = data.total_unread;
                if (data.total_unread > 0) {
                    waTotalUnreadBadge.classList.remove('d-none');
                } else {
                    waTotalUnreadBadge.classList.add('d-none');
                }
            }

            if (waActiveWindowsCount) {
                waActiveWindowsCount.textContent = data.active_windows_count;
            }

            // Play sound if unread increased
            if (data.total_unread > prevTotalUnread) {
                playNotificationChime();
            }
            prevTotalUnread = data.total_unread;

            // 2. Sync sidebar conversations list
            if (waConvoList && Array.isArray(data.conversations)) {
                const emptyNotice = document.getElementById('waEmptyConvoNotice');
                if (data.conversations.length > 0 && emptyNotice) {
                    emptyNotice.remove();
                }

                data.conversations.forEach((convo, index) => {
                    let item = document.getElementById(`convo-item-${convo.id}`);
                    const isActive = activeConvoId === convo.id;

                    if (item) {
                        // Update existing item
                        const previewEl = item.querySelector('.wa-convo-preview');
                        if (previewEl) previewEl.textContent = convo.last_message_preview;

                        const timeEl = item.querySelector('.wa-convo-time');
                        if (timeEl) timeEl.textContent = convo.last_message_time;

                        const dotEl = item.querySelector('.wa-convo-window-dot');
                        if (dotEl) {
                            if (convo.is_window_open) dotEl.classList.remove('d-none');
                            else dotEl.classList.add('d-none');
                        }

                        const unreadEl = item.querySelector('.wa-convo-unread-pill');
                        if (unreadEl) {
                            unreadEl.textContent = convo.unread_count;
                            if (convo.unread_count > 0) unreadEl.classList.remove('d-none');
                            else unreadEl.classList.add('d-none');
                        }

                        // Reorder to top if index is 0 and it's not already top
                        if (index === 0 && waConvoList.firstElementChild !== item) {
                            waConvoList.prepend(item);
                        }
                    } else {
                        // Dynamically append new conversation thread
                        const convoLink = `{{ route('admin.whatsapp.inbox') }}?conversation_id=${convo.id}&filter=${encodeURIComponent(currentFilter)}&search=${encodeURIComponent(currentSearch)}`;
                        const newItem = document.createElement('a');
                        newItem.href = convoLink;
                        newItem.className = `wa-convo-item d-block p-3 border-bottom text-decoration-none transition-all ${isActive ? 'active' : ''}`;
                        newItem.id = `convo-item-${convo.id}`;
                        newItem.setAttribute('data-convo-id', convo.id);

                        newItem.innerHTML = `
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="position-relative flex-shrink-0">
                                    <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="background-color: ${convo.avatar_color};">
                                        ${convo.avatar_letter}
                                    </div>
                                    <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1 wa-convo-window-dot ${convo.is_window_open ? '' : 'd-none'}"
                                          title="24h Service Window Active"></span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center justify-content-between mb-0.5">
                                        <span class="fw-semibold text-dark text-truncate d-block small wa-convo-name" style="max-width: 140px;">
                                            ${escapeHtml(convo.contact_name)}
                                        </span>
                                        <span class="text-muted wa-convo-time" style="font-size: 0.7rem;">
                                            ${convo.last_message_time}
                                        </span>
                                    </div>
                                    <div class="text-muted small text-truncate mb-1 wa-convo-preview" style="font-size: 0.78rem;">
                                        ${escapeHtml(convo.last_message_preview)}
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge wa-convo-window-badge ${convo.is_window_open ? 'bg-success-subtle text-success' : 'bg-light text-muted'}" style="font-size: 0.65rem;">
                                                ${convo.is_window_open ? 'Open' : 'Window Closed'}
                                            </span>
                                        </div>
                                        <span class="badge rounded-pill bg-success text-white px-2 py-0.5 wa-convo-unread-pill ${convo.unread_count > 0 ? '' : 'd-none'}" style="font-size: 0.7rem;">
                                            ${convo.unread_count}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                        waConvoList.prepend(newItem);
                    }
                });
            }
        } catch (e) {
            console.warn('Directory polling error:', e);
        } finally {
            isDirectoryPolling = false;
        }
    }

    // Start sidebar polling every 5.5 seconds
    const dirPollInterval = setInterval(pollDirectory, 5500);

    @if ($activeConversation)
    // -------------------------------------------------------------
    // Active Conversation Real-Time Message Poller & Composer Engine
    // -------------------------------------------------------------
    const stream = document.getElementById('waChatStream');
    const sendForm = document.getElementById('waSendForm');
    const messageInput = document.getElementById('waMessageInput');
    const btnAttachFile = document.getElementById('btnAttachFile');
    const fileInput = document.getElementById('waFileInput');
    const attachmentPreview = document.getElementById('waAttachmentPreview');
    const attachmentThumb = document.getElementById('waAttachmentThumb');
    const attachmentThumbImg = attachmentThumb ? attachmentThumb.querySelector('img') : null;
    const attachmentGenericIcon = document.getElementById('waAttachmentGenericIcon');
    const attachmentName = document.getElementById('waAttachmentName');
    const attachmentSize = document.getElementById('waAttachmentSize');
    const removeAttachment = document.getElementById('waRemoveAttachment');
    const btnRefresh = document.getElementById('btnRefreshChat');
    const activeWindowBadge = document.getElementById('activeWindowBadge');
    const activeWindowLabel = document.getElementById('activeWindowLabel');
    const expiredNotice = document.getElementById('waWindowExpiredNotice');

    const conversationId = {{ $activeConversation->id }};
    const sendUrl = '{{ route('admin.whatsapp.inbox.send', $activeConversation->id) }}';
    const sendMediaUrl = '{{ route('admin.whatsapp.inbox.send-media', $activeConversation->id) }}';
    const pollUrl = '{{ route('admin.whatsapp.inbox.poll', $activeConversation->id) }}';
    const assignUrl = '{{ route('admin.whatsapp.inbox.assign', $activeConversation->id) }}';
    const statusUrl = '{{ route('admin.whatsapp.inbox.status', $activeConversation->id) }}';
    const noteUrl = '{{ route('admin.whatsapp.inbox.note', $activeConversation->id) }}';

    let lastMessageId = {{ $messages->last()?->id ?? 0 }};
    let isPolling = false;
    let selectedFile = null;
    let currentComposerMode = 'reply';

    const btnModeReply = document.getElementById('btnModeReply');
    const btnModeNote = document.getElementById('btnModeNote');
    const composerWrapper = document.getElementById('waComposerWrapper');
    const modeHintText = document.getElementById('modeHintText');

    if (btnModeReply && btnModeNote) {
        btnModeReply.addEventListener('click', () => {
            currentComposerMode = 'reply';
            btnModeReply.className = 'btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode active bg-white text-dark shadow-xs';
            btnModeNote.className = 'btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode text-muted';
            composerWrapper.style.backgroundColor = '#ffffff';
            modeHintText.innerHTML = '<i class="bi bi-info-circle me-1"></i>Sends message directly to customer\'s WhatsApp';
            messageInput.placeholder = 'Type a message or type / for quick replies...';
            btnAttachFile.classList.remove('d-none');
            const btnSend = document.getElementById('btnSendMessage');
            btnSend.className = 'btn btn-success rounded-circle btn-icon p-2.5 flex-shrink-0 shadow-sm';
            btnSend.innerHTML = '<i class="bi bi-send-fill text-white fs-6"></i>';
        });

        btnModeNote.addEventListener('click', () => {
            currentComposerMode = 'note';
            btnModeNote.className = 'btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode active bg-white text-dark shadow-xs';
            btnModeReply.className = 'btn btn-xs rounded-pill px-3 py-1 fw-semibold btn-mode text-muted';
            composerWrapper.style.backgroundColor = '#fffbeb';
            modeHintText.innerHTML = '<strong style="color: #92400e;"><i class="bi bi-lock-fill"></i> Internal Staff Note:</strong> Visible to team only. Customer will NOT receive this.';
            messageInput.placeholder = 'Type a private team note...';
            btnAttachFile.classList.add('d-none');
            const btnSend = document.getElementById('btnSendMessage');
            btnSend.className = 'btn btn-warning rounded-circle btn-icon p-2.5 flex-shrink-0 shadow-sm';
            btnSend.innerHTML = '<i class="bi bi-sticky-fill text-dark fs-6"></i>';
        });
    }

    function scrollToBottom() {
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }
    }
    scrollToBottom();

    // Auto-expand textarea
    messageInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Enter to send (Shift+Enter for newline)
    messageInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendForm.dispatchEvent(new Event('submit'));
        }
    });

    // Attachment handlers
    btnAttachFile.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            selectedFile = this.files[0];
            attachmentName.textContent = selectedFile.name;
            attachmentSize.textContent = (selectedFile.size / (1024 * 1024)).toFixed(2) + ' MB';
            attachmentPreview.classList.remove('d-none');

            if (selectedFile.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (attachmentThumbImg) attachmentThumbImg.src = e.target.result;
                    attachmentThumb.style.display = 'block';
                    attachmentGenericIcon.style.display = 'none';
                };
                reader.readAsDataURL(selectedFile);
            } else {
                attachmentThumb.style.display = 'none';
                attachmentGenericIcon.style.display = 'block';
            }
        }
    });

    removeAttachment.addEventListener('click', function () {
        selectedFile = null;
        fileInput.value = '';
        attachmentPreview.classList.add('d-none');
    });

    // Send Form
    sendForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const text = messageInput.value.trim();

        if (!text && !selectedFile) return;

        const btnSend = document.getElementById('btnSendMessage');
        btnSend.disabled = true;
        btnSend.innerHTML = '<span class="spinner-border spinner-border-sm text-white" role="status"></span>';

        try {
            if (currentComposerMode === 'note') {
                const response = await fetch(noteUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ note: text }),
                });
                const res = await response.json();

                if (res.success) {
                    if (res.rendered_html) {
                        const temp = document.createElement('div');
                        temp.innerHTML = res.rendered_html.trim();
                        stream.appendChild(temp.firstChild);
                    }
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                } else {
                    alert('Error: ' + (res.message || 'Failed to save note'));
                }
            } else if (selectedFile) {
                const formData = new FormData();
                formData.append('file', selectedFile);
                if (text) formData.append('caption', text);
                formData.append('_token', csrfToken);

                const response = await fetch(sendMediaUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData,
                });
                const res = await response.json();

                if (res.success) {
                    appendMessage(res.chat_message);
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    removeAttachment.click();
                } else {
                    alert('Error: ' + (res.message || 'Failed to send file'));
                }
            } else {
                const response = await fetch(sendUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ message: text }),
                });
                const res = await response.json();

                if (res.success) {
                    appendMessage(res.chat_message);
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    updateWindowState(res.window_open, res.window_label);
                } else {
                    alert('Error: ' + (res.message || 'Failed to send message'));
                }
            }
        } catch (err) {
            console.error('Send error:', err);
            alert('Failed to send message. Please check internet connection.');
        } finally {
            btnSend.disabled = false;
            if (currentComposerMode === 'note') {
                btnSend.innerHTML = '<i class="bi bi-sticky-fill text-dark fs-6"></i>';
            } else {
                btnSend.innerHTML = '<i class="bi bi-send-fill text-white fs-6"></i>';
            }
            scrollToBottom();
        }
    });

    // Agent assignment
    document.querySelectorAll('.assign-agent-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const adminId = this.getAttribute('data-admin-id') || null;
            try {
                const res = await fetch(assignUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ admin_id: adminId }),
                });
                const data = await res.json();
                if (data.success) {
                    const label = document.getElementById('assignedAgentLabel');
                    if (label) label.textContent = data.agent_name;
                }
            } catch (e) {
                console.error('Assignment error', e);
            }
        });
    });

    // Status changes
    document.querySelectorAll('.change-status-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const newStatus = this.getAttribute('data-status');
            try {
                const res = await fetch(statusUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ status: newStatus }),
                });
                const data = await res.json();
                if (data.success) {
                    const label = document.getElementById('convoStatusLabel');
                    if (label) label.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                }
            } catch (e) {
                console.error('Status error', e);
            }
        });
    });

    function appendMessage(msg) {
        lastMessageId = Math.max(lastMessageId, msg.id);

        const emptyMsgNotice = document.getElementById('waEmptyMessageNotice');
        if (emptyMsgNotice) emptyMsgNotice.remove();

        const time = new Date(msg.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-end mb-2';
        div.setAttribute('id', `msg-${msg.id}`);
        div.setAttribute('data-message-id', msg.id);

        let content = '';
        if (msg.message_type === 'image') {
            const url = msg.media_url ? '{{ asset('storage') }}/' + msg.media_url : '#';
            content = `<div class="mb-1"><img src="${url}" class="rounded-3 img-fluid" style="max-height: 220px;" alt="Photo"></div>`;
            if (msg.body) content += `<div>${escapeHtml(msg.body)}</div>`;
        } else if (msg.message_type === 'document') {
            content = `<div class="p-2 rounded bg-light-subtle d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                <div class="text-truncate fw-semibold small" style="max-width: 180px;">${escapeHtml(msg.media_filename || 'Document')}</div>
            </div>`;
            if (msg.body) content += `<div>${escapeHtml(msg.body)}</div>`;
        } else if (msg.message_type === 'template') {
            const header = msg.media_url ? `<div class="mb-1.5"><img src="${msg.media_url}" class="rounded-3 img-fluid" style="max-height: 200px; object-fit: cover;"></div>` : '';
            content = `${header}<div class="mb-1"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;"><i class="bi bi-lightning-charge-fill me-1"></i>Approved Meta Template</span></div><div>${escapeHtml(msg.body || '')}</div>`;
        } else {
            content = `<div>${escapeHtml(msg.body || '')}</div>`;
        }

        div.innerHTML = `
            <div class="wa-bubble wa-bubble-outbound p-2.5 px-3">
                ${content}
                <div class="d-flex align-items-center justify-content-end gap-1 mt-1 text-muted" style="font-size: 0.68rem;">
                    <span>${time}</span>
                    <span class="message-status-tick">
                        <i class="bi bi-check2 text-muted" title="Sent"></i>
                    </span>
                </div>
            </div>
        `;

        stream.appendChild(div);
        scrollToBottom();
    }

    function updateWindowState(isOpen, label) {
        if (activeWindowLabel) activeWindowLabel.textContent = label;
        if (activeWindowBadge) {
            activeWindowBadge.className = 'badge ' + (isOpen ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border');
        }
        if (expiredNotice) {
            if (isOpen) expiredNotice.classList.add('d-none');
            else expiredNotice.classList.remove('d-none');
        }
    }

    // Active Chat Message Polling
    async function pollMessages() {
        if (isPolling) return;
        isPolling = true;

        try {
            const res = await fetch(`${pollUrl}?after_id=${lastMessageId}`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();

            // 1. Append newly received or sent messages
            if (data.messages && data.messages.length > 0) {
                const emptyMsgNotice = document.getElementById('waEmptyMessageNotice');
                if (emptyMsgNotice) emptyMsgNotice.remove();

                data.messages.forEach(msg => {
                    lastMessageId = Math.max(lastMessageId, msg.id);

                    // Check if already in stream
                    if (document.getElementById(`msg-${msg.id}`)) return;

                    const isOut = msg.direction === 'outbound';
                    const wrapper = document.createElement('div');
                    wrapper.className = `d-flex ${isOut ? 'justify-content-end' : 'justify-content-start'} mb-2`;
                    wrapper.setAttribute('id', `msg-${msg.id}`);
                    wrapper.setAttribute('data-message-id', msg.id);

                    let bodyHtml = '';
                    if (msg.message_type === 'image' && msg.media_url) {
                        bodyHtml = `<div class="mb-1"><img src="${msg.media_url}" class="rounded-3 img-fluid" style="max-height: 220px;"></div>`;
                        if (msg.body) bodyHtml += `<div>${escapeHtml(msg.body)}</div>`;
                    } else if (msg.message_type === 'document') {
                        bodyHtml = `<div class="p-2 rounded bg-light-subtle d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                            <div class="text-truncate fw-semibold small">${escapeHtml(msg.media_filename || 'Document')}</div>
                            <a href="${msg.media_url || '#'}" target="_blank" class="btn btn-sm btn-light p-1 ms-auto"><i class="bi bi-download"></i></a>
                        </div>`;
                        if (msg.body) bodyHtml += `<div>${escapeHtml(msg.body)}</div>`;
                    } else if (msg.message_type === 'template') {
                        const header = msg.media_url ? `<div class="mb-1.5"><img src="${msg.media_url}" class="rounded-3 img-fluid" style="max-height: 200px; object-fit: cover;"></div>` : '';
                        bodyHtml = `${header}<div class="mb-1"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill" style="font-size: 0.65rem;"><i class="bi bi-lightning-charge-fill me-1"></i>Approved Meta Template</span></div><div>${escapeHtml(msg.body || '')}</div>`;
                    } else {
                        bodyHtml = `<div>${escapeHtml(msg.body || '')}</div>`;
                    }

                    const tick = msg.status === 'read' ? '<i class="bi bi-check2-all text-primary fw-bold" title="Read"></i>' :
                                 (msg.status === 'delivered' ? '<i class="bi bi-check2-all text-muted" title="Delivered"></i>' :
                                 (msg.status === 'failed' ? '<i class="bi bi-exclamation-circle text-danger" title="Failed"></i>' :
                                 '<i class="bi bi-check2 text-muted" title="Sent"></i>'));

                    wrapper.innerHTML = `
                        <div class="wa-bubble ${isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound'} p-2.5 px-3">
                            ${bodyHtml}
                            <div class="d-flex align-items-center ${isOut ? 'justify-content-end' : 'justify-content-start'} gap-1 mt-1 text-muted" style="font-size: 0.68rem;">
                                <span>${msg.created_at_time}</span>
                                ${isOut ? `<span class="message-status-tick">${tick}</span>` : ''}
                            </div>
                        </div>
                    `;

                    stream.appendChild(wrapper);
                });

                scrollToBottom();
            }

            // 2. Real-time Delivery Status Sync (sent -> delivered -> read)
            if (data.statuses && Array.isArray(data.statuses)) {
                data.statuses.forEach(item => {
                    const bubble = document.querySelector(`[data-message-id="${item.id}"]`);
                    if (bubble) {
                        const tickSpan = bubble.querySelector('.message-status-tick');
                        if (tickSpan) {
                            if (item.status === 'read') {
                                tickSpan.innerHTML = '<i class="bi bi-check2-all text-primary fw-bold" title="Read by customer"></i>';
                            } else if (item.status === 'delivered') {
                                tickSpan.innerHTML = '<i class="bi bi-check2-all text-muted" title="Delivered to phone"></i>';
                            } else if (item.status === 'failed') {
                                tickSpan.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger" title="Failed to deliver"></i>';
                            }
                        }
                    }
                });
            }

            if (data.window_label) {
                updateWindowState(data.window_open, data.window_label);
            }
        } catch (e) {
            console.warn('Active chat polling error:', e);
        } finally {
            isPolling = false;
        }
    }

    // Active message stream poll every 3.5 seconds
    const chatPollInterval = setInterval(pollMessages, 3500);

    if (btnRefresh) {
        btnRefresh.addEventListener('click', function () {
            this.classList.add('rotate-spin');
            Promise.all([pollMessages(), pollDirectory()]).finally(() => {
                setTimeout(() => this.classList.remove('rotate-spin'), 600);
            });
        });
    }

    // -------------------------------------------------------------
    // Meta Template Modal & Interactive Preview
    // -------------------------------------------------------------
    const tmplSelect = document.getElementById('tmplSelect');
    const customTmplGroup = document.getElementById('customTmplNameGroup');
    const customTmplInput = document.getElementById('customTmplName');
    const tmplDynamicFields = document.getElementById('tmplDynamicFields');
    const tmplHeaderImageUrl = document.getElementById('tmplHeaderImageUrl');
    const previewHeaderImg = document.getElementById('previewHeaderImage');
    const previewHeaderImgTag = previewHeaderImg ? previewHeaderImg.querySelector('img') : null;
    const previewTitle = document.getElementById('previewTitle');
    const previewBodyText = document.getElementById('previewBodyText');
    const templateForm = document.getElementById('waSendTemplateForm');
    const btnSubmitTemplate = document.getElementById('btnSubmitTemplate');
    const sendTemplateUrl = '{{ route('admin.whatsapp.inbox.send-template', $activeConversation->id) }}';
    const customerName = '{{ addslashes($activeConversation->contact_name ?: "Customer") }}';
    const recentOrderNumber = '{{ $customerContext["orders"]->first()?->order_number ?? "VAN-1001" }}';

    const templateSchemas = {
        order_confirmation: {
            title: "VANRITI — Order Confirmed 🌿",
            text: "Hello {1}, thank you for your order! Your order #{2} is confirmed and will be delivered by {3}. Track your order here:",
            fields: [
                { id: "p1", label: "Customer Name {{1}}", default: customerName },
                { id: "p2", label: "Order Number {{2}}", default: recentOrderNumber },
                { id: "p3", label: "Delivery Date {{3}}", default: "in 3-5 business days" },
            ]
        },
        abandoned_cart_recovery: {
            title: "Items left in your cart! 🌿",
            text: "Hello {1}, you left items in your VANRITI cart! Complete your order now with 10% OFF at {2}",
            fields: [
                { id: "p1", label: "Customer Name {{1}}", default: customerName },
                { id: "p2", label: "Checkout Link {{2}}", default: "{{ url('/cart') }}" },
            ]
        },
        vanriti_welcome: {
            title: "Welcome to VANRITI 🌿",
            text: "Hello {1}, welcome to VANRITI! Discover the pure essence of Ayurveda and natural holistic skincare with special offers on your first purchase.",
            fields: [
                { id: "p1", label: "Customer Name {{1}}", default: customerName },
            ]
        },
        promotional_offer: {
            title: "Special VIP Offer 🎉",
            text: "Hello {1}, celebrate with us! Enjoy {2} on your next order using coupon code: {3} at checkout.",
            fields: [
                { id: "p1", label: "Customer Name {{1}}", default: customerName },
                { id: "p2", label: "Offer Title {{2}}", default: "Flat 15% OFF" },
                { id: "p3", label: "Coupon Code {{3}}", default: "FESTIVE15" },
            ]
        },
        custom: {
            title: "Custom Meta Template",
            text: "Template variables: {1}, {2}",
            fields: [
                { id: "p1", label: "Parameter {{1}}", default: customerName },
                { id: "p2", label: "Parameter {{2}}", default: "" },
            ]
        }
    };

    function renderTemplateFields() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.order_confirmation;

        if (customTmplGroup) {
            if (key === 'custom') {
                customTmplGroup.classList.remove('d-none');
                if (customTmplInput) customTmplInput.required = true;
            } else {
                customTmplGroup.classList.add('d-none');
                if (customTmplInput) customTmplInput.required = false;
            }
        }

        if (tmplDynamicFields) {
            tmplDynamicFields.innerHTML = schema.fields.map((f, idx) => `
                <div class="mb-3">
                    <label class="form-label fw-bold small text-dark">${f.label}</label>
                    <input type="text" class="form-control rounded-3 tmpl-param-input" data-index="${idx+1}" value="${f.default}" required>
                </div>
            `).join('');

            tmplDynamicFields.querySelectorAll('.tmpl-param-input').forEach(inp => {
                inp.addEventListener('input', updateTemplatePreview);
            });
        }

        updateTemplatePreview();
    }

    function updateTemplatePreview() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.order_confirmation;

        if (previewTitle) previewTitle.textContent = schema.title;

        let body = schema.text;
        const inputs = tmplDynamicFields ? tmplDynamicFields.querySelectorAll('.tmpl-param-input') : [];
        inputs.forEach((inp, idx) => {
            const token = '{' + (idx + 1) + '}';
            const val = inp.value || token;
            body = body.split(token).join(val);
        });

        if (previewBodyText) previewBodyText.innerHTML = escapeHtml(body);

        if (tmplHeaderImageUrl && previewHeaderImg && previewHeaderImgTag) {
            const url = tmplHeaderImageUrl.value.trim();
            if (url) {
                previewHeaderImgTag.src = url;
                previewHeaderImg.classList.remove('d-none');
            } else {
                previewHeaderImg.classList.add('d-none');
            }
        }
    }

    if (tmplSelect) {
        tmplSelect.addEventListener('change', renderTemplateFields);
        renderTemplateFields();
    }

    if (tmplHeaderImageUrl) {
        tmplHeaderImageUrl.addEventListener('input', updateTemplatePreview);
    }

    if (templateForm) {
        templateForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const key = tmplSelect.value;
            const templateName = key === 'custom' ? customTmplInput.value.trim() : key;
            const langCode = document.getElementById('tmplLangCode')?.value.trim() || 'en';
            const headerUrl = tmplHeaderImageUrl?.value.trim() || null;

            const params = [];
            if (tmplDynamicFields) {
                tmplDynamicFields.querySelectorAll('.tmpl-param-input').forEach(inp => {
                    params.push(inp.value.trim());
                });
            }

            btnSubmitTemplate.disabled = true;
            btnSubmitTemplate.innerHTML = '<span class="spinner-border spinner-border-sm text-white" role="status"></span> Sending...';

            try {
                const response = await fetch(sendTemplateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        template_name: templateName,
                        lang_code: langCode,
                        parameters: params,
                        header_image_url: headerUrl,
                    }),
                });

                const res = await response.json();
                if (res.success) {
                    appendMessage(res.chat_message);
                    const modalEl = document.getElementById('sendTemplateModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    scrollToBottom();
                } else {
                    alert('Error: ' + (res.message || 'Failed to dispatch Meta template'));
                }
            } catch (err) {
                console.error('Template send error:', err);
                alert('Network or server error while dispatching template.');
            } finally {
                btnSubmitTemplate.disabled = false;
                btnSubmitTemplate.innerHTML = '<i class="bi bi-send-fill me-1"></i>Send Template Now';
            }
        });
    }

    // -------------------------------------------------------------
    // Quick Replies & Canned Snippets Autocomplete
    // -------------------------------------------------------------
    const quickReplyApiUrl = "{{ route('admin.whatsapp.bot.quick-replies.api') }}";
    const waQuickReplyMenu = document.getElementById('waQuickReplyMenu');
    const waSlashSuggestions = document.getElementById('waSlashSuggestions');
    let quickRepliesCache = [];

    async function loadQuickReplies() {
        try {
            const res = await fetch(quickReplyApiUrl);
            const data = await res.json();
            if (data.success && data.replies) {
                quickRepliesCache = data.replies;
                renderQuickReplyMenu();
            }
        } catch (e) {
            console.error('Failed to load quick replies', e);
        }
    }

    function renderQuickReplyMenu() {
        if (!waQuickReplyMenu) return;
        if (!quickRepliesCache.length) {
            waQuickReplyMenu.innerHTML = '<li class="dropdown-header small text-muted px-2">No quick replies configured yet.</li>';
            return;
        }

        waQuickReplyMenu.innerHTML = '<li class="dropdown-header small text-uppercase fw-bold text-muted px-2 py-1">⚡ Quick Reply Snippets</li>' +
            quickRepliesCache.map(qr => `
                <li>
                    <button type="button" class="dropdown-item rounded-3 py-2 px-2 text-start qr-select-btn" data-shortcut="${qr.shortcut}">
                        <div class="d-flex align-items-center justify-content-between mb-0.5">
                            <span class="fw-bold small text-dark">${escapeHtml(qr.title)}</span>
                            <span class="badge bg-light text-primary border font-monospace" style="font-size: 0.65rem;">/${qr.shortcut}</span>
                        </div>
                        <div class="text-muted small text-truncate" style="max-width: 280px; font-size: 0.75rem;">${escapeHtml(qr.content)}</div>
                    </button>
                </li>
            `).join('');

        waQuickReplyMenu.querySelectorAll('.qr-select-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                insertQuickReply(btn.getAttribute('data-shortcut'));
            });
        });
    }

    function insertQuickReply(shortcut) {
        const qr = quickRepliesCache.find(r => r.shortcut === shortcut);
        if (!qr || !messageInput) return;

        let content = qr.content;
        content = content.replace('{name}', customerName || 'Customer');
        content = content.replace('{site_url}', "{{ url('/') }}");

        messageInput.value = content;
        if (waSlashSuggestions) waSlashSuggestions.classList.add('d-none');
        messageInput.focus();
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
    }

    if (messageInput && waSlashSuggestions) {
        messageInput.addEventListener('input', function() {
            const val = this.value;
            if (val.startsWith('/')) {
                const query = val.slice(1).toLowerCase();
                const matched = quickRepliesCache.filter(r => r.shortcut.toLowerCase().includes(query) || r.title.toLowerCase().includes(query));
                if (matched.length > 0) {
                    waSlashSuggestions.innerHTML = matched.map(qr => `
                        <div class="p-2 rounded-2 hover-bg-light cursor-pointer slash-item d-flex align-items-center justify-content-between" data-shortcut="${qr.shortcut}" style="cursor: pointer;">
                            <div>
                                <span class="fw-bold small text-dark">/${qr.shortcut}</span>
                                <span class="text-muted ms-2 small">${escapeHtml(qr.title)}</span>
                            </div>
                            <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">${qr.category || 'General'}</span>
                        </div>
                    `).join('');
                    waSlashSuggestions.classList.remove('d-none');

                    waSlashSuggestions.querySelectorAll('.slash-item').forEach(item => {
                        item.addEventListener('click', () => {
                            insertQuickReply(item.getAttribute('data-shortcut'));
                        });
                    });
                } else {
                    waSlashSuggestions.classList.add('d-none');
                }
            } else {
                waSlashSuggestions.classList.add('d-none');
            }
        });

        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && waSlashSuggestions) {
                waSlashSuggestions.classList.add('d-none');
            }
        });
    }

    loadQuickReplies();
    @endif

    // Cleanup intervals on window unload
    window.addEventListener('beforeunload', () => {
        clearInterval(dirPollInterval);
        @if ($activeConversation)
            clearInterval(chatPollInterval);
        @endif
    });
});
</script>
@endpush
