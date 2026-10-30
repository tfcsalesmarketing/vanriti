@extends('admin.layouts.app')

@section('title', 'Vanriti WhatsApp Live Chat Inbox')

@push('styles')
<style>
/* Full Bleed Neat & Clean WhatsApp Experience matching TFC Premium Design */
.admin-content {
    padding: 0 !important;
}
.whatsapp-app-container {
    height: calc(100vh - 65px);
    display: flex;
    flex-direction: column;
    background: #f6f8fa;
    padding: 12px 16px 14px 16px;
    gap: 12px;
    overflow: hidden;
    position: relative;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    box-sizing: border-box;
}
@media (max-width: 767.98px) {
    .whatsapp-app-container {
        height: calc(100vh - 58px);
        height: calc(100dvh - 58px);
        padding: 6px;
        gap: 8px;
    }
}

/* 1. Top Executive KPI Bar */
.wa-kpi-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
}
.wa-kpi-bar::-webkit-scrollbar {
    display: none;
}
.wa-kpi-card {
    background: #ffffff;
    border: 1px solid #eef2f5;
    border-radius: 12px;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: #1e293b;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    transition: all 0.15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.wa-kpi-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}
.wa-kpi-card.active {
    border-bottom: 2.5px solid #107548;
}
.wa-kpi-icon {
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #334155;
}
.wa-kpi-card.active .wa-kpi-icon {
    color: #25D366;
}
.wa-kpi-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 6px;
}
.wa-kpi-subtitle {
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.2;
    margin-top: 2px;
}
.wa-btn-new-broadcast {
    background: #e7f8f0;
    color: #107548;
    border: 1px solid #c2ecd6;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.82rem;
    padding: 7px 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    margin-left: auto;
    flex-shrink: 0;
    transition: all 0.15s ease;
}
.wa-btn-new-broadcast:hover {
    background: #107548;
    color: #ffffff;
}

/* 2. Main 3-Column Split Workspace */
.wa-workspace-row {
    flex-grow: 1;
    display: flex;
    gap: 12px;
    min-height: 0;
    overflow: hidden;
}

/* Column 1: Conversations Directory */
.wa-directory-card {
    width: 340px;
    min-width: 300px;
    background: #ffffff;
    border: 1px solid #eef2f5;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    flex-shrink: 0;
}
@media (max-width: 991.98px) {
    .wa-directory-card {
        width: 100%;
        display: {{ $activeConversation ? 'none' : 'flex' }};
    }
}

.wa-directory-header {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    flex-shrink: 0;
}
.wa-search-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.wa-search-input-wrap i {
    position: absolute;
    left: 12px;
    color: #94a3b8;
    font-size: 0.85rem;
}
.wa-search-input {
    width: 100%;
    padding: 7px 12px 7px 34px;
    background: #f1f5f9;
    border: 1px solid transparent;
    border-radius: 20px;
    font-size: 0.82rem;
    outline: none;
    transition: all 0.15s ease;
}
.wa-search-input:focus {
    background: #ffffff;
    border-color: #25D366;
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
}
.wa-filter-tabs {
    display: flex;
    gap: 6px;
    margin-top: 10px;
    overflow-x: auto;
    scrollbar-width: none;
}
.wa-filter-tabs::-webkit-scrollbar {
    display: none;
}
.wa-filter-pill {
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
    text-decoration: none;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    white-space: nowrap;
    transition: all 0.12s ease;
}
.wa-filter-pill:hover, .wa-filter-pill.active {
    background: #107548;
    color: #ffffff;
    border-color: #107548;
}

.wa-conversation-list {
    flex-grow: 1;
    overflow-y: auto;
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.wa-convo-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    border: 1px solid transparent;
    transition: all 0.12s ease;
}
.wa-convo-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.wa-convo-item.active {
    background: #e7f8f0;
    border-color: #bbf0d6;
}
.wa-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #107548;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    flex-shrink: 0;
    position: relative;
}
.wa-avatar-badge {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    background: #25D366;
}
.wa-convo-content {
    flex-grow: 1;
    min-width: 0;
}
.wa-convo-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2px;
}
.wa-convo-time {
    font-size: 0.7rem;
    color: #94a3b8;
    font-weight: 400;
}
.wa-convo-preview {
    font-size: 0.75rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 4px;
}
.wa-unread-badge {
    background: #25D366;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 9999px;
    margin-left: auto;
}

/* Column 2: Active Chat Card */
.wa-chat-card {
    flex-grow: 1;
    background: #efeae2;
    background-image: radial-gradient(#d5cdc2 0.75px, transparent 0.75px);
    background-size: 16px 16px;
    border: 1px solid #e2dcd2;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    position: relative;
    min-width: 0;
}
@media (max-width: 991.98px) {
    .wa-chat-card {
        display: {{ $activeConversation ? 'flex' : 'none' }};
    }
}

.wa-chat-header {
    background: #ffffff;
    padding: 10px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-shrink: 0;
    z-index: 10;
}
.wa-chat-stream {
    flex-grow: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    scroll-behavior: smooth;
}
.wa-bubble {
    max-width: 72%;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 0.85rem;
    line-height: 1.45;
    box-shadow: 0 1px 1px rgba(0,0,0,0.08);
    position: relative;
    word-break: break-word;
}
.wa-bubble-inbound {
    background: #ffffff;
    color: #0f172a;
    align-self: flex-start;
    border-top-left-radius: 0;
}
.wa-bubble-outbound {
    background: #d9fdd3;
    color: #0f172a;
    align-self: flex-end;
    border-top-right-radius: 0;
}
.wa-bubble-meta {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 3px;
    font-size: 0.65rem;
    color: #64748b;
    margin-top: 3px;
}
.wa-bubble-meta i {
    font-size: 0.75rem;
}
.wa-bubble-meta .text-primary {
    color: #53bdeb !important;
}

/* Composer Bar */
.wa-composer-container {
    background: #f0f2f5;
    padding: 10px 14px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.wa-composer-bar {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.wa-composer-icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #54656f;
    font-size: 1.25rem;
    background: transparent;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.12s ease;
    flex-shrink: 0;
}
.wa-composer-icon:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.wa-input-capsule {
    flex-grow: 1;
    background: #ffffff;
    border-radius: 18px;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    border: 1px solid #d1d7db;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}
.wa-input-capsule textarea {
    width: 100%;
    border: none;
    outline: none;
    resize: none;
    font-size: 0.88rem;
    line-height: 1.4;
    background: transparent;
    color: #0f172a;
    max-height: 120px;
    padding: 0;
    font-family: inherit;
}
.wa-send-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #25D366;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    transition: transform 0.12s ease, background 0.15s ease;
}
.wa-send-btn:hover {
    background: #1da851;
    transform: scale(1.04);
}
.wa-send-btn:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    transform: none;
}

/* Column 3: Contact & Order 360 Context Drawer */
.wa-context-card {
    width: 320px;
    background: #ffffff;
    border: 1px solid #eef2f5;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    flex-shrink: 0;
}
@media (max-width: 1199.98px) {
    .wa-context-card {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="whatsapp-app-container">

    {{-- Top Executive WhatsApp KPI Bar --}}
    <div class="wa-kpi-bar">
        <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'all']) }}" class="wa-kpi-card {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}">
            <div class="wa-kpi-icon"><i class="bi bi-chat-square-dots"></i></div>
            <div>
                <div class="wa-kpi-title">{{ $stats['total'] ?? $conversations->total() }}</div>
                <div class="wa-kpi-subtitle">Vanriti Direct Chats</div>
            </div>
        </a>

        <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'unread']) }}" class="wa-kpi-card {{ ($filter ?? '') === 'unread' ? 'active' : '' }}">
            <div class="wa-kpi-icon text-warning"><i class="bi bi-envelope-exclamation"></i></div>
            <div>
                <div class="wa-kpi-title text-warning">
                    {{ $stats['unread'] ?? $unreadCount }}
                    @if (($stats['unread'] ?? $unreadCount) > 0)
                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">Action</span>
                    @endif
                </div>
                <div class="wa-kpi-subtitle">Unread Incoming</div>
            </div>
        </a>

        <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'active_window']) }}" class="wa-kpi-card {{ ($filter ?? '') === 'active_window' ? 'active' : '' }}">
            <div class="wa-kpi-icon text-success"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="wa-kpi-title text-success">{{ $stats['active_windows'] ?? $activeWindowsCount }}</div>
                <div class="wa-kpi-subtitle">Free 24h Window</div>
            </div>
        </a>

        <div class="wa-kpi-card">
            <div class="wa-kpi-icon text-info"><i class="bi bi-patch-check"></i></div>
            <div>
                <div class="wa-kpi-title">
                    {{ $isConfigured ? 'Connected' : 'Unconfigured' }}
                    <span class="badge {{ $isConfigured ? 'bg-success' : 'bg-danger' }} rounded-pill ms-1" style="font-size: 0.6rem;">
                        {{ $isConfigured ? 'Meta Cloud API' : 'Check Settings' }}
                    </span>
                </div>
                <div class="wa-kpi-subtitle">Vanriti WABA Channel</div>
            </div>
        </div>

        <a href="{{ route('admin.whatsapp.campaigns.create') }}" class="wa-btn-new-broadcast">
            <i class="bi bi-megaphone"></i>
            <span>Create Campaign</span>
        </a>
    </div>

    {{-- Main 3-Column Split Workspace --}}
    <div class="wa-workspace-row">

        {{-- Column 1: Conversations Directory --}}
        <aside class="wa-directory-card">
            <div class="wa-directory-header">
                <form action="{{ route('admin.whatsapp.inbox') }}" method="GET">
                    <div class="wa-search-input-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" class="wa-search-input" placeholder="Search customer, phone, text..." value="{{ $search ?? '' }}">
                    </div>
                    @if (!empty($filter) && $filter !== 'all')
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                </form>

                <div class="wa-filter-tabs">
                    <a href="{{ route('admin.whatsapp.inbox', ['search' => $search ?? '']) }}" class="wa-filter-pill {{ empty($filter) || $filter === 'all' ? 'active' : '' }}">
                        All
                    </a>
                    <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'unread', 'search' => $search ?? '']) }}" class="wa-filter-pill {{ ($filter ?? '') === 'unread' ? 'active' : '' }}">
                        Unread
                    </a>
                    <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'active_window', 'search' => $search ?? '']) }}" class="wa-filter-pill {{ ($filter ?? '') === 'active_window' ? 'active' : '' }}">
                        24h Active
                    </a>
                    <a href="{{ route('admin.whatsapp.inbox', ['filter' => 'resolved', 'search' => $search ?? '']) }}" class="wa-filter-pill {{ ($filter ?? '') === 'resolved' ? 'active' : '' }}">
                        Resolved
                    </a>
                </div>
            </div>

            <div class="wa-conversation-list" id="waConvoList">
                @forelse ($conversations as $convo)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $convo->id;
                    @endphp
                    <a href="{{ route('admin.whatsapp.inbox', ['conversation' => $convo->id, 'filter' => $filter, 'search' => $search]) }}"
                       class="wa-convo-item {{ $isActive ? 'active' : '' }}"
                       data-convo-id="{{ $convo->id }}">
                        <div class="wa-avatar" style="background: #107548;">
                            {{ strtoupper(substr($convo->contact_name ?: $convo->phone, 0, 1)) }}
                            @if ($convo->isWindowOpen())
                                <span class="wa-avatar-badge" title="24h Service Window Active"></span>
                            @endif
                        </div>
                        <div class="wa-convo-content">
                            <div class="wa-convo-title">
                                <span class="text-truncate">{{ $convo->contact_name ?: '+' . $convo->phone }}</span>
                                <span class="wa-convo-time">
                                    {{ $convo->last_message_at ? $convo->last_message_at->diffForHumans(null, true, true) : '' }}
                                </span>
                            </div>
                            <div class="wa-convo-preview">
                                @if ($convo->latestMessage && $convo->latestMessage->direction === 'outbound')
                                    <i class="bi bi-check2 text-muted"></i>
                                @endif
                                <span class="text-truncate">{{ $convo->last_message_preview ?: 'Start chatting...' }}</span>
                                @if ($convo->unread_count > 0)
                                    <span class="wa-unread-badge">{{ $convo->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-chat-dots fs-3 d-block mb-2 text-muted opacity-50"></i>
                        <span class="small">No Vanriti conversations found.</span>
                    </div>
                @endforelse
            </div>
        </aside>

        {{-- Column 2: Active Chat Card --}}
        @if ($activeConversation)
            <main class="wa-chat-card">
                {{-- Clean WhatsApp Header --}}
                <header class="wa-chat-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-lg-none">
                            <a href="{{ route('admin.whatsapp.inbox', ['filter' => $filter, 'search' => $search]) }}" class="btn btn-sm btn-light rounded-circle">
                                <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                        <div class="wa-avatar" style="width: 40px; height: 40px; font-size: 0.9rem; background: #107548;">
                            {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark" style="font-size: 0.92rem;">
                                    {{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }}
                                </span>
                                <span class="badge {{ $activeConversation->status === 'resolved' ? 'bg-secondary' : ($activeConversation->status === 'pending' ? 'bg-warning text-dark' : 'bg-success') }}" style="font-size: 0.65rem;">
                                    {{ ucfirst($activeConversation->status) }}
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-telephone me-1"></i>+{{ $activeConversation->phone }}
                                @if ($activeConversation->isWindowOpen())
                                    <span class="text-success ms-2">● 24h Window Active</span>
                                @else
                                    <span class="text-muted ms-2">○ Template Required</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions on the right --}}
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteChatModal" title="Permanently delete conversation, chat history, files and logs">
                            <i class="bi bi-trash3"></i>
                            <span class="d-none d-lg-inline">Delete Chat</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#sendTemplateModal">
                            <i class="bi bi-file-earmark-text text-primary"></i>
                            <span class="d-none d-lg-inline">Template</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" onclick="document.getElementById('waDetailColumn').classList.toggle('d-none');">
                            <i class="bi bi-layout-sidebar-inset-reverse"></i>
                            <span class="d-none d-lg-inline">Details</span>
                        </button>
                    </div>
                </header>

                {{-- Scrollable Message Stream --}}
                <div class="wa-chat-stream" id="waMessageStream">
                    @forelse ($messages as $msg)
                        @php
                            $isOut = $msg->direction === 'outbound';
                        @endphp
                        <div class="d-flex {{ $isOut ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $msg->id }}">
                            <div class="wa-bubble {{ $isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound' }} p-3">
                                {{-- Sender attribution if outbound --}}
                                @if ($isOut && $msg->sender)
                                    <div class="text-muted small mb-1" style="font-size: 0.68rem; font-weight: 600;">
                                        {{ $msg->sender->name }}
                                    </div>
                                @endif

                                {{-- Media Rendering --}}
                                @if ($msg->message_type === 'image' && $msg->media_url)
                                    <div class="mb-2 rounded overflow-hidden" style="max-width: 280px;">
                                        <a href="{{ $msg->media_url }}" target="_blank">
                                            <img src="{{ $msg->media_url }}" class="img-fluid rounded" alt="WhatsApp Media">
                                        </a>
                                    </div>
                                @elseif ($msg->message_type === 'document' && $msg->media_url)
                                    <div class="mb-2 p-2 bg-white rounded border d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                                        <div class="text-truncate">
                                            <a href="{{ $msg->media_url }}" target="_blank" class="fw-bold text-dark text-decoration-none small">
                                                Download Attached PDF
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                {{-- Text Body --}}
                                <div style="white-space: pre-wrap;">{{ $msg->body }}</div>

                                {{-- Meta Timestamp & Double Checkmarks --}}
                                <div class="wa-bubble-meta">
                                    <span>{{ $msg->sent_at ? $msg->sent_at->format('h:i A') : $msg->created_at->format('h:i A') }}</span>
                                    @if ($isOut)
                                        @if ($msg->status === 'read')
                                            <i class="bi bi-check2-all text-primary" title="Read"></i>
                                        @elseif ($msg->status === 'delivered')
                                            <i class="bi bi-check2-all text-muted" title="Delivered"></i>
                                        @elseif ($msg->status === 'sent')
                                            <i class="bi bi-check2 text-muted" title="Sent"></i>
                                        @elseif ($msg->status === 'failed')
                                            <i class="bi bi-exclamation-circle text-danger" title="Failed"></i>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center my-auto p-4 text-muted">
                            <i class="bi bi-chat-square-heart fs-1 text-success opacity-50 d-block mb-2"></i>
                            <h6 class="fw-bold">No messages in this chat yet.</h6>
                            <p class="small text-muted mb-0">Send a greeting message or an approved Meta template to start conversation.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Composer Bar --}}
                <div class="wa-composer-container">
                    <form id="waComposerForm" onsubmit="event.preventDefault(); sendChatMessage();">
                        <div class="wa-composer-bar">
                            {{-- Quick replies dropdown --}}
                            <div class="dropdown">
                                <button type="button" class="wa-composer-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Replies">
                                    <i class="bi bi-lightning-charge"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-start shadow-sm" style="max-height: 280px; overflow-y: auto;">
                                    <li class="dropdown-header text-uppercase small fw-bold">Quick Replies</li>
                                    @forelse ($quickReplies as $qr)
                                        <li>
                                            <a class="dropdown-item small" href="javascript:void(0)" onclick="insertQuickReply({{ json_encode($qr->content) }})">
                                                <strong>/{{ $qr->shortcut }}</strong> - {{ $qr->title }}
                                            </a>
                                        </li>
                                    @empty
                                        <li class="dropdown-item small text-muted">No quick replies found</li>
                                    @endforelse
                                </ul>
                            </div>

                            {{-- Attachment / Media button --}}
                            <button type="button" class="wa-composer-icon" data-bs-toggle="modal" data-bs-target="#sendMediaModal" title="Send Media">
                                <i class="bi bi-paperclip"></i>
                            </button>

                            {{-- Input capsule with Enter to Send --}}
                            <div class="wa-input-capsule">
                                <textarea id="waMessageInput" rows="1" placeholder="Type a message or '/' for quick replies... (Enter to send, Shift+Enter for new line)" onkeydown="handleComposerKey(event)"></textarea>
                            </div>

                            {{-- Send button --}}
                            <button type="submit" class="wa-send-btn" id="waSendBtn" title="Send Message">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            {{-- Column 3: Contact & Order 360 Context Drawer --}}
            <aside class="wa-context-card d-none d-lg-flex" id="waDetailColumn">
                {{-- Header & Profile Summary --}}
                <div class="p-3 border-bottom text-center bg-light">
                    <div class="wa-avatar mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.3rem; background: #107548;">
                        {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $activeConversation->contact_name ?: 'Guest Customer' }}</h6>
                    <div class="text-muted small">+{{ $activeConversation->phone }}</div>
                    <div class="mt-2">
                        <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-sm btn-outline-success py-1 px-3 rounded-pill" style="font-size: 0.75rem;">
                            <i class="bi bi-telephone-outbound me-1"></i>Direct Call
                        </a>
                    </div>
                </div>

                {{-- Lifetime Customer Metrics (Spent, Orders, AOV) --}}
                @php
                    $user = $customerContext['user'] ?? null;
                    $orders = $customerContext['orders'] ?? collect();
                    $totalSpent = $customerContext['total_spent'] ?? 0;
                    $orderCount = $customerContext['order_count'] ?? 0;
                    $aov = $orderCount > 0 ? ($totalSpent / $orderCount) : 0;
                    $session = $customerContext['checkout_session'] ?? null;
                @endphp
                <div class="p-3 border-bottom">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted small d-block" style="font-size: 0.65rem;">SPENT</span>
                                <strong class="small text-dark" style="font-size: 0.8rem;">{{ format_price($totalSpent) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted small d-block" style="font-size: 0.65rem;">ORDERS</span>
                                <strong class="small text-dark" style="font-size: 0.8rem;">{{ $orderCount }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded bg-light border">
                                <span class="text-muted small d-block" style="font-size: 0.65rem;">AOV</span>
                                <strong class="small text-dark" style="font-size: 0.8rem;">{{ format_price($aov) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Orders Section --}}
                @if ($orders->isNotEmpty())
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-dark"><i class="bi bi-bag-check me-1"></i>Recent Orders</span>
                            @if ($user)
                                <a href="{{ route('admin.orders.index', ['search' => $activeConversation->phone]) }}" class="small text-primary text-decoration-none" style="font-size: 0.72rem;">View All</a>
                            @endif
                        </div>
                        @foreach ($orders->take(3) as $ord)
                            <div class="p-2 mb-2 bg-light rounded border" style="font-size: 0.75rem;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.orders.show', $ord->id) }}" class="fw-bold text-dark text-decoration-none">
                                        #{{ $ord->order_number }}
                                    </a>
                                    <span class="fw-semibold text-success">{{ format_price($ord->grand_total) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 text-muted" style="font-size: 0.68rem;">
                                    <span>{{ $ord->created_at->format('d M, Y') }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ ucfirst($ord->order_status) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- In-Progress Cart / Checkout Recovery (if any) --}}
                @if ($session)
                    <div class="p-3 border-bottom bg-warning-subtle">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold small text-dark"><i class="bi bi-cart3 me-1"></i>In-Progress Cart</span>
                            <span class="badge bg-warning text-dark" style="font-size: 0.62rem;">{{ strtoupper($session->status) }}</span>
                        </div>
                        <div class="small text-dark mb-1">Value: <strong>{{ format_price($session->grand_total) }}</strong></div>
                        @if (!empty($session->cart_data['items']))
                            <div class="small text-muted" style="font-size: 0.7rem;">
                                {{ count($session->cart_data['items']) }} items in abandoned cart
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Assignment & Status Control --}}
                <div class="p-3 border-bottom">
                    <label class="form-label small fw-bold text-dark mb-1">Assigned Support Agent</label>
                    <select class="form-select form-select-sm mb-3" onchange="assignAgent(this.value)">
                        <option value="">Unassigned</option>
                        @foreach ($admins as $adm)
                            <option value="{{ $adm->id }}" {{ $activeConversation->assigned_to == $adm->id ? 'selected' : '' }}>
                                {{ $adm->name }}
                            </option>
                        @endforeach
                    </select>

                    <label class="form-label small fw-bold text-dark mb-1">Conversation Status</label>
                    <select class="form-select form-select-sm" onchange="changeStatus(this.value)">
                        <option value="open" {{ $activeConversation->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="pending" {{ $activeConversation->status === 'pending' ? 'selected' : '' }}>Pending Follow-up</option>
                        <option value="resolved" {{ $activeConversation->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>

                {{-- Private Internal Notes --}}
                <div class="p-3 flex-grow-1">
                    <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-sticky me-1"></i>Internal Team Notes</h6>
                    <div id="notesContainer" class="mb-2" style="max-height: 180px; overflow-y: auto;">
                        @php
                            $notes = (array) ($activeConversation->notes ?? []);
                        @endphp
                        @forelse ($notes as $n)
                            <div class="p-2 mb-2 bg-light rounded border text-dark" style="font-size: 0.75rem;">
                                <div>{{ $n['note'] ?? '' }}</div>
                                <div class="text-muted mt-1" style="font-size: 0.65rem;">
                                    {{ $n['admin_name'] ?? 'Staff' }} • {{ !empty($n['created_at']) ? \Carbon\Carbon::parse($n['created_at'])->diffForHumans() : '' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small text-center py-2">No internal notes yet.</div>
                        @endforelse
                    </div>

                    <form id="noteForm" onsubmit="event.preventDefault(); submitInternalNote();">
                        <textarea id="noteInput" class="form-control form-control-sm mb-2" rows="2" placeholder="Add confidential note..."></textarea>
                        <button type="submit" class="btn btn-xs btn-outline-dark w-100 py-1" style="font-size: 0.72rem;">
                            Save Internal Note
                        </button>
                    </form>

                    <div class="pt-3 mt-3 border-top">
                        <button type="button" class="btn btn-xs btn-outline-danger w-100 py-1.5 d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="modal" data-bs-target="#deleteChatModal" style="font-size: 0.75rem;">
                            <i class="bi bi-trash3"></i>
                            <span>Delete Chat & Purge Data</span>
                        </button>
                    </div>
                </div>
            </aside>
        @else
            <main class="wa-chat-card d-flex align-items-center justify-content-center text-center p-5">
                <div>
                    <div class="wa-avatar mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem; background: #25D366;">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Vanriti WhatsApp Live Chat</h5>
                    <p class="text-muted small" style="max-width: 380px;">
                        Select a conversation from the directory on the left to start live chatting, answer customer queries, or assist with orders.
                    </p>
                </div>
            </main>
        @endif

    </div>
</div>

{{-- Modal 1: Send Template --}}
@if ($activeConversation)
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Send Meta Template</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="templateForm" onsubmit="event.preventDefault(); sendTemplateMessage();">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Choose Approved Template</label>
                        <select id="templateSelect" class="form-select form-select-sm" required onchange="renderTemplatePreview(this)">
                            <option value="">Select a template...</option>
                            @foreach ($approvedTemplates as $tmpl)
                                <option value="{{ $tmpl->name }}" data-lang="{{ $tmpl->language }}" data-body="{{ $tmpl->body_text }}">
                                    {{ $tmpl->name }} ({{ $tmpl->language }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="templateBodyPreview" class="p-3 bg-light rounded border text-muted small mb-3 d-none"></div>

                    <div id="templateParamsWrapper" class="mb-3 d-none">
                        <label class="form-label small fw-semibold">Dynamic Parameters</label>
                        <div id="templateParamsFields" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Send Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal 2: Send Media --}}
<div class="modal fade" id="sendMediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold"><i class="bi bi-paperclip text-success me-2"></i>Send Media Attachment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="mediaForm" onsubmit="event.preventDefault(); sendMediaMessage();">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Media Type</label>
                        <select id="mediaTypeSelect" class="form-select form-select-sm">
                            <option value="image">Image (JPEG / PNG)</option>
                            <option value="document">Document (PDF)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Direct Media File URL</label>
                        <input type="url" id="mediaUrlInput" class="form-control form-control-sm" placeholder="https://..." required>
                        <small class="text-muted">Public AWS S3 or hosted CDN URL.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Caption (Optional)</label>
                        <input type="text" id="mediaCaptionInput" class="form-control form-control-sm" placeholder="Add a caption...">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success">Send Media</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal 3: Delete Chat & Complete Database Purge --}}
<div class="modal fade" id="deleteChatModal" tabindex="-1" aria-labelledby="deleteChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-danger text-white">
                <h6 class="modal-title fw-bold" id="deleteChatModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Permanently Delete Chat?</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.whatsapp.inbox.destroy', $activeConversation) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body p-4">
                    <p class="text-dark fw-semibold mb-2">Are you sure you want to completely purge this entire chat?</p>
                    <div class="alert alert-warning small mb-3">
                        <i class="bi bi-info-circle me-1"></i> This action is <strong>irreversible</strong> and will permanently wipe:
                        <ul class="mb-0 mt-2 ps-3">
                            <li>All inbound & outbound WhatsApp messages</li>
                            <li>All uploaded files & media (photos, PDF documents)</li>
                            <li>All raw incoming webhook logs and audit trail for this phone number</li>
                            <li>Any active checkout cart sessions</li>
                            <li>The conversation contact record</li>
                        </ul>
                    </div>
                    <p class="text-muted small mb-0">
                        Target Contact: <strong>{{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }} (+{{ $activeConversation->phone }})</strong>
                    </p>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold">
                        <i class="bi bi-trash3 me-1"></i> Yes, Permanently Delete Everything
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script nonce="{{ $cspNonce }}">
const activeConvId = {{ $activeConversation ? $activeConversation->id : 'null' }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Auto-scroll stream to bottom
function scrollToBottom() {
    const stream = document.getElementById('waMessageStream');
    if (stream) {
        stream.scrollTop = stream.scrollHeight;
    }
}

// Handle Enter to send
function handleComposerKey(e) {
    if ((e.key === 'Enter' || e.keyCode === 13) && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();

    // Attach robust Enter-to-send listener on message input
    const msgInput = document.getElementById('waMessageInput');
    if (msgInput) {
        msgInput.addEventListener('keydown', function(e) {
            if ((e.key === 'Enter' || e.keyCode === 13) && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });

        msgInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
    }

    // Attach submit listener on composer form
    const compForm = document.getElementById('waComposerForm');
    if (compForm) {
        compForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendChatMessage();
        });
    }
});

// Insert quick reply into textarea
function insertQuickReply(text) {
    const input = document.getElementById('waMessageInput');
    if (input) {
        input.value = text;
        input.focus();
    }
}

// Send Chat Message via AJAX
function sendChatMessage() {
    if (!activeConvId) return;
    const input = document.getElementById('waMessageInput');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    const btn = document.getElementById('waSendBtn');
    if (btn) btn.disabled = true;

    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/send`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ message: msg })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Append message optimistically
            const stream = document.getElementById('waMessageStream');
            const row = document.createElement('div');
            row.className = 'd-flex justify-content-end';
            row.innerHTML = `
                <div class="wa-bubble wa-bubble-outbound p-3">
                    <div style="white-space: pre-wrap;">${escapeHtml(msg)}</div>
                    <div class="d-flex align-items-center justify-content-end gap-1 mt-1 text-muted" style="font-size: 0.65rem;">
                        <span>Just now</span>
                        <i class="bi bi-check2 text-muted"></i>
                    </div>
                </div>
            `;
            stream.appendChild(row);
            scrollToBottom();
        } else {
            alert('Failed to send: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Error sending message: ' + err.message);
    })
    .finally(() => {
        if (btn) btn.disabled = false;
        input.focus();
    });
}

// Send Template Message
function sendTemplateMessage() {
    if (!activeConvId) return;
    const select = document.getElementById('templateSelect');
    const tmplName = select.value;
    const opt = select.options[select.selectedIndex];
    const lang = opt ? opt.getAttribute('data-lang') : 'en';

    const paramInputs = document.querySelectorAll('#templateParamsFields input');
    const params = Array.from(paramInputs).map(inp => inp.value);

    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/send-template`, {
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
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('sendTemplateModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            window.location.reload();
        } else {
            alert('Template send failed: ' + (data.message || 'Check template variables'));
        }
    })
    .catch(err => alert('Error sending template: ' + err.message));
}

// Send Media Message
function sendMediaMessage() {
    if (!activeConvId) return;
    const type = document.getElementById('mediaTypeSelect').value;
    const url = document.getElementById('mediaUrlInput').value.trim();
    const caption = document.getElementById('mediaCaptionInput').value.trim();

    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/send-media`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            media_type: type,
            media_url: url,
            caption: caption
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('sendMediaModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            window.location.reload();
        } else {
            alert('Media send failed: ' + (data.message || 'Error'));
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

// Assign Agent
function assignAgent(adminId) {
    if (!activeConvId) return;
    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/assign`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ admin_id: adminId || null })
    });
}

// Change Status
function changeStatus(newStatus) {
    if (!activeConvId) return;
    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    });
}

// Submit Internal Note
function submitInternalNote() {
    if (!activeConvId) return;
    const input = document.getElementById('noteInput');
    const note = input.value.trim();
    if (!note) return;

    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/note`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ note: note })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            window.location.reload();
        }
    });
}

// Template dynamic preview
function renderTemplatePreview(select) {
    const opt = select.options[select.selectedIndex];
    const preview = document.getElementById('templateBodyPreview');
    const paramsWrapper = document.getElementById('templateParamsWrapper');
    const paramsFields = document.getElementById('templateParamsFields');

    if (!opt || !opt.value) {
        preview.classList.add('d-none');
        paramsWrapper.classList.add('d-none');
        return;
    }

    const body = opt.getAttribute('data-body') || '';
    preview.innerText = body;
    preview.classList.remove('d-none');

    // Detect {{1}}, {{2}}...
    const matches = body.match(/\{\{(\d+)\}\}/g);
    paramsFields.innerHTML = '';
    if (matches && matches.length > 0) {
        const unique = [...new Set(matches)];
        unique.forEach((m, idx) => {
            const row = document.createElement('div');
            row.className = 'input-group input-group-sm';
            row.innerHTML = `
                <span class="input-group-text">${m}</span>
                <input type="text" class="form-control" placeholder="Value for variable ${idx + 1}" required>
            `;
            paramsFields.appendChild(row);
        });
        paramsWrapper.classList.remove('d-none');
    } else {
        paramsWrapper.classList.add('d-none');
    }
}

// Real-time live polling for new messages
setInterval(function() {
    if (!activeConvId) return;
    const stream = document.getElementById('waMessageStream');
    const lastMsgEl = stream ? stream.querySelector('[data-message-id]:last-child') : null;
    const afterId = lastMsgEl ? (lastMsgEl.getAttribute('data-message-id') || 0) : 0;

    fetch(`{{ url('admin/whatsapp/inbox') }}/${activeConvId}/poll?after_id=${afterId}`)
    .then(r => r.json())
    .then(data => {
        if (data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(msg => {
                const isOut = msg.direction === 'outbound';
                const row = document.createElement('div');
                row.className = isOut ? 'd-flex justify-content-end' : 'd-flex justify-content-start';
                row.setAttribute('data-message-id', msg.id);
                row.innerHTML = `
                    <div class="wa-bubble ${isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound'} p-3">
                        <div style="white-space: pre-wrap;">${escapeHtml(msg.body || '')}</div>
                        <div class="d-flex align-items-center justify-content-end gap-1 mt-1 text-muted" style="font-size: 0.65rem;">
                            <span>Just now</span>
                            ${isOut ? '<i class="bi bi-check2 text-muted"></i>' : ''}
                        </div>
                    </div>
                `;
                stream.appendChild(row);
            });
            scrollToBottom();
        }
    })
    .catch(() => {});
}, 4500);

function escapeHtml(str) {
    const div = document.createElement('div');
    div.innerText = str;
    return div.innerHTML;
}
</script>
@endpush
@endsection
