@extends('admin.layouts.app')

@section('title', 'WhatsApp Inbox & Live Chat')

@push('styles')
<style>
/* Full Bleed WhatsApp Web Experience */
.admin-content {
    padding: 0 !important;
}
.whatsapp-app-container {
    height: calc(100vh - 65px);
    display: flex;
    flex-direction: column;
    background: #f0f2f5;
    overflow: hidden;
    position: relative;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
@media (max-width: 767.98px) {
    .whatsapp-app-container {
        height: calc(100vh - 58px);
        height: calc(100dvh - 58px);
    }
}

/* 1. Top Executive Operational Bar */
.wa-top-bar {
    height: 48px;
    background: #ffffff;
    border-bottom: 1px solid #e9edef;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    z-index: 10;
}
.wa-top-bar .brand-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #111b21;
    letter-spacing: -0.01em;
}
.wa-top-bar .brand-badge i {
    color: #25D366;
    font-size: 1.3rem;
}

/* 2. Main Workspace Layout */
.wa-workspace {
    flex: 1 1 0;
    min-height: 0;
    display: flex;
    width: 100%;
    height: 100%;
    background: #ffffff;
    overflow: hidden;
    position: relative;
}

/* Left Sidebar: Conversations Directory */
.wa-sidebar-column {
    width: 380px;
    min-width: 320px;
    max-width: 420px;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-right: 1px solid #d1d7db;
    overflow: hidden;
    flex-shrink: 0;
}
@media (max-width: 991.98px) {
    .wa-sidebar-column {
        width: 100%;
        max-width: 100%;
    }
}
.wa-sidebar-header {
    height: 56px;
    background: #f0f2f5;
    border-bottom: 1px solid #e9edef;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.wa-search-section {
    padding: 8px 12px;
    background: #ffffff;
    border-bottom: 1px solid #f0f2f5;
    flex-shrink: 0;
}
.wa-search-pill {
    background: #f0f2f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    height: 36px;
}
.wa-search-pill input {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    font-size: 14px;
    color: #111b21;
    margin-left: 8px;
}
.wa-search-pill input::placeholder {
    color: #8696a0;
}
.wa-filter-tabs {
    display: flex;
    gap: 6px;
    padding: 8px 12px;
    overflow-x: auto;
    scrollbar-width: none;
    border-bottom: 1px solid #e9edef;
    background: #ffffff;
    flex-shrink: 0;
    -ms-overflow-style: none;
}
.wa-filter-tabs::-webkit-scrollbar {
    display: none;
}
.wa-filter-chip {
    padding: 5px 12px;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    color: #54656f;
    background: #f0f2f5;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.wa-filter-chip.active {
    background: #e7fce3;
    color: #008069;
    font-weight: 600;
}
.wa-filter-chip:hover:not(.active) {
    background: #e9edef;
    color: #111b21;
}
.wa-convo-list {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    background: #ffffff;
}
.wa-convo-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f5;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease;
    position: relative;
}
.wa-convo-item:hover {
    background: #f5f6f6;
}
.wa-convo-item.active {
    background: #f0f2f5;
}
.wa-convo-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #25D366;
}
.wa-avatar {
    width: 46px;
    height: 46px;
    font-size: 1.05rem;
    font-weight: 700;
}

/* Middle: Chat Window */
.wa-chat-column {
    flex: 1 1 0;
    min-width: 0;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}
.wa-chat-header {
    height: 56px;
    background: #f0f2f5;
    border-bottom: 1px solid #d1d7db;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    z-index: 5;
}
.wa-chat-stream {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    background-color: #efeae2;
    background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23000000' fill-opacity='0.035' fill-rule='evenodd'%3E%3Cpath d='M11 14.01V7a1 1 0 0 1 2 0v7.01A5.001 5.001 0 0 1 17 19v1a1 1 0 0 1-2 0v-1a3 3 0 0 0-3-3h-1.5A3.5 3.5 0 0 1 7 12.5V11a1 1 0 0 1 2 0v1.5a1.5 1.5 0 0 0 1.5 1.5H11zM65 41a5 5 0 1 1-10 0 5 5 0 0 1 10 0zm-2 0a3 3 0 1 0-6 0 3 3 0 0 0 6 0zM22 66a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-1 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0zm53-24a1 1 0 0 1 1 1v1.5a3.5 3.5 0 0 1-3.5 3.5H71a3 3 0 0 0-3 3v1a1 1 0 0 1-2 0v-1a5.001 5.001 0 0 1 4-4.99V43a1 1 0 0 1 1-1h2.5a1.5 1.5 0 0 0 1.5-1.5V39a1 1 0 0 1 1-1zm-48-9a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm-2 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0z'/%3E%3C/g%3E%3C/svg%3E");
    padding: 16px 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    scroll-behavior: smooth;
}
.wa-date-divider {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    padding: 5px 14px;
    border-radius: 9999px;
    font-size: 0.73rem;
    font-weight: 600;
    color: #54656f;
    box-shadow: 0 1px 1px rgba(11,20,26,0.12);
    display: inline-flex;
    align-items: center;
    letter-spacing: 0.01em;
}
.wa-bubble {
    max-width: 68%;
    position: relative;
    box-shadow: 0 1px 0.5px rgba(11,20,26,0.13);
    word-break: break-word;
    font-size: 0.9rem;
    line-height: 1.45;
}
.wa-bubble-inbound {
    background-color: #ffffff;
    border-radius: 8px 8px 8px 0px;
    color: #111b21;
}
.wa-bubble-outbound {
    background-color: #d9fdd3;
    border-radius: 8px 8px 0px 8px;
    color: #111b21;
}

/* WhatsApp Web Bottom Composer */
.wa-composer-wrapper {
    background: #f0f2f5;
    border-top: 1px solid #d1d7db;
    padding: 8px 16px 10px 16px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
    z-index: 5;
    transition: background-color 0.2s ease;
}
.wa-composer-wrapper.internal-note-mode {
    background: #fffbeb !important;
    border-top-color: #fde68a !important;
}
.wa-mode-toggle {
    display: inline-flex;
    background: rgba(0,0,0,0.06);
    border-radius: 9999px;
    padding: 2px;
}
.wa-mode-toggle .btn-mode {
    border-radius: 9999px;
    padding: 3px 12px;
    font-size: 0.72rem;
    font-weight: 600;
    border: none;
    color: #54656f;
    background: transparent;
    transition: all 0.15s ease;
}
.wa-mode-toggle .btn-mode.active {
    background: #ffffff;
    color: #111b21;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}
.wa-mode-toggle .btn-mode.note-active {
    background: #f59e0b;
    color: #ffffff;
}
.wa-input-container {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    width: 100% !important;
}
.wa-input-capsule {
    background: #ffffff;
    border: 1px solid #d1d7db;
    border-radius: 22px;
    padding: 8px 16px;
    min-height: 44px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    position: relative;
    width: 100%;
}
.wa-input-capsule:focus-within {
    border-color: #00a884;
    box-shadow: 0 1px 3px rgba(0, 168, 132, 0.15);
}
.wa-input-capsule textarea {
    width: 100% !important;
    min-width: 0 !important;
    border: none !important;
    outline: none !important;
    resize: none !important;
    background: transparent !important;
    font-size: 15px !important;
    color: #111b21 !important;
    line-height: 1.45 !important;
    padding: 0 !important;
    margin: 0 !important;
    max-height: 120px !important;
}
.wa-input-capsule textarea::placeholder {
    color: #8696a0;
}
.wa-action-icon-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #54656f;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}
.wa-action-icon-btn:hover {
    background: #e9edef;
    color: #111b21;
}
.wa-send-circle-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background: #25D366;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    cursor: pointer;
    flex-shrink: 0;
    margin-bottom: 1px;
    box-shadow: 0 2px 4px rgba(37, 211, 102, 0.3);
    transition: transform 0.15s, background 0.15s, box-shadow 0.15s;
}
.wa-send-circle-btn:hover {
    background: #20bd5a;
    transform: scale(1.04);
    box-shadow: 0 3px 8px rgba(37, 211, 102, 0.4);
}
.wa-send-circle-btn:active {
    transform: scale(0.96);
}

/* Right Sidebar: Customer Context Drawer */
.wa-context-column {
    width: 320px;
    min-width: 300px;
    max-width: 350px;
    height: 100%;
    min-height: 0;
    background: #ffffff;
    border-left: 1px solid #d1d7db;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
}
.wa-context-header {
    height: 56px;
    background: #f0f2f5;
    border-bottom: 1px solid #d1d7db;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

/* Custom Scrollbars */
.wa-convo-list::-webkit-scrollbar,
.wa-chat-stream::-webkit-scrollbar,
.wa-context-column::-webkit-scrollbar {
    width: 5px;
}
.wa-convo-list::-webkit-scrollbar-thumb,
.wa-chat-stream::-webkit-scrollbar-thumb,
.wa-context-column::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.14);
    border-radius: 9999px;
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
<div class="whatsapp-app-container">

    {{-- 1. Top Executive Operational Header (Single sleek row) --}}
    <header class="wa-top-bar" id="waOperationalToolbar">
        <div class="d-flex align-items-center gap-2.5">
            <div class="brand-badge">
                <i class="bi bi-whatsapp"></i>
                <span>WhatsApp Inbox</span>
            </div>

            {{-- Unread Badge --}}
            <span id="waTotalUnreadBadge" class="badge rounded-pill bg-danger text-white px-2 py-0.5 {{ $totalUnread > 0 ? '' : 'd-none' }}" style="font-size: 0.7rem;">
                <span id="waTotalUnreadCount">{{ $totalUnread }}</span> unread
            </span>

            {{-- 24h Windows Badge --}}
            <span id="waActiveWindowsBadge" class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-0.5" style="font-size: 0.7rem;">
                <i class="bi bi-clock-history me-1"></i><span id="waActiveWindowsCount">{{ $activeWindowsCount }}</span> active windows (24h)
            </span>

            {{-- Live Sync Indicator --}}
            <span id="waRealtimeSyncBadge" class="badge bg-white text-muted border px-2 py-0.5 rounded-pill d-none d-md-inline-flex align-items-center gap-1.5 shadow-2xs" style="font-size: 0.68rem;" title="Real-time Webhook Auto-Sync Active">
                <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 6px; height: 6px;"></span>
                <span>Live Sync</span>
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            {{-- Navigation Shortcuts --}}
            <div class="d-none d-lg-inline-flex gap-1 p-0.5 bg-light rounded-pill border">
                <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="btn btn-xs rounded-pill px-2.5 py-1 text-muted text-decoration-none d-inline-flex align-items-center gap-1 hover-dark" style="font-size: 0.72rem;">
                    <i class="bi bi-broadcast text-primary"></i>
                    <span>Broadcasts</span>
                </a>
                <a href="{{ route('admin.whatsapp.templates.index') }}" class="btn btn-xs rounded-pill px-2.5 py-1 text-muted text-decoration-none d-inline-flex align-items-center gap-1 hover-dark" style="font-size: 0.72rem;">
                    <i class="bi bi-file-earmark-code text-secondary"></i>
                    <span>Templates</span>
                </a>
                <a href="{{ route('admin.whatsapp.bot.index') }}" class="btn btn-xs rounded-pill px-2.5 py-1 text-muted text-decoration-none d-inline-flex align-items-center gap-1 hover-dark" style="font-size: 0.72rem;">
                    <i class="bi bi-robot text-success"></i>
                    <span>Auto-Reply Rules</span>
                </a>
            </div>

            {{-- Meta Connection Status --}}
            @if ($isConfigured)
                <span class="badge bg-success-subtle text-success border border-success px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                    <i class="bi bi-shield-check"></i>
                    Meta Cloud API Connected
                </span>
            @else
                <a href="{{ route('admin.settings.index') }}#whatsapp" class="badge bg-warning-subtle text-warning border border-warning px-2.5 py-1 rounded-pill text-decoration-none" style="font-size: 0.72rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>API Setup Required
                </a>
            @endif
        </div>
    </header>

    {{-- 2. Main 3-Column WhatsApp Web Grid --}}
    <div class="wa-workspace">

        {{-- Column 1: Conversations Directory --}}
        <aside class="wa-sidebar-column {{ $activeConversation ? 'd-none d-md-flex' : '' }}" id="waSidebarColumn">
            {{-- Sidebar Top Action Bar --}}
            <div class="wa-sidebar-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-2xs"
                         style="background-color: #008069; width: 36px; height: 36px; font-size: 0.95rem;">
                        {{ strtoupper(substr(auth('admin')->user()->name ?? 'V', 0, 1)) }}
                    </div>
                    <span class="fw-semibold text-dark small">{{ auth('admin')->user()->name ?? 'Agent' }}</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('admin.whatsapp.templates.index') }}" class="btn btn-sm btn-light border-0 rounded-circle wa-action-icon-btn" title="View Meta Templates">
                        <i class="bi bi-chat-left-dots"></i>
                    </a>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="wa-search-section">
                <form method="GET" action="{{ route('admin.whatsapp.inbox') }}" id="waSearchForm">
                    <div class="wa-search-pill">
                        <i class="bi bi-search text-muted" style="font-size: 0.9rem;"></i>
                        <input type="text" name="search" id="waSearchInput" value="{{ $search }}"
                               placeholder="Search or start new chat...">
                        @if ($search)
                            <a href="{{ route('admin.whatsapp.inbox', ['filter' => $filter]) }}" class="text-muted text-decoration-none hover-dark ms-1">&times;</a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- WhatsApp Filter Chips --}}
            <nav class="wa-filter-tabs wa-filter-pills">
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'all']) }}"
                   class="wa-filter-chip {{ $filter === 'all' ? 'active' : '' }}">
                    All
                </a>
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'mine']) }}"
                   class="wa-filter-chip {{ $filter === 'mine' ? 'active' : '' }}">
                    Mine
                </a>
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'unassigned']) }}"
                   class="wa-filter-chip {{ $filter === 'unassigned' ? 'active' : '' }}">
                    Unassigned
                </a>
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'unread']) }}"
                   class="wa-filter-chip {{ $filter === 'unread' ? 'active' : '' }}">
                    Unread @if($totalUnread > 0)<span class="badge rounded-pill bg-success text-white ms-1" style="font-size: 0.65rem;">{{ $totalUnread }}</span>@endif
                </a>
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'window_open']) }}"
                   class="wa-filter-chip {{ $filter === 'window_open' ? 'active' : '' }}">
                    Open (24h)
                </a>
                <a href="{{ route('admin.whatsapp.inbox', ['search' => $search, 'filter' => 'resolved']) }}"
                   class="wa-filter-chip {{ $filter === 'resolved' ? 'active' : '' }}">
                    Resolved
                </a>
            </nav>

            {{-- Conversations List --}}
            <div class="wa-convo-list" id="waConvoList">
                @forelse ($conversations as $convo)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $convo->id;
                        $hasWindow = $convo->isWindowOpen();
                    @endphp
                    <a href="{{ route('admin.whatsapp.inbox', ['conversation_id' => $convo->id, 'filter' => $filter, 'search' => $search]) }}"
                       class="wa-convo-item {{ $isActive ? 'active' : '' }}"
                       id="convo-item-{{ $convo->id }}"
                       data-convo-id="{{ $convo->id }}">
                        <div class="position-relative flex-shrink-0">
                            <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white shadow-2xs"
                                 style="background-color: {{ '#' . substr(md5($convo->phone), 0, 6) }};">
                                {{ strtoupper(substr($convo->contact_name ?: $convo->phone, 0, 1)) }}
                            </div>
                            <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1 wa-convo-window-dot {{ $hasWindow ? '' : 'd-none' }}"
                                  title="24h Service Window Active"></span>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-semibold text-dark text-truncate d-block wa-convo-name" style="max-width: 170px; font-size: 0.95rem;">
                                    {{ $convo->contact_name ?: '+' . $convo->phone }}
                                </span>
                                <span class="text-muted wa-convo-time" style="font-size: 0.72rem;">
                                    {{ $convo->last_message_at ? $convo->last_message_at->shortRelativeDiffForHumans() : '' }}
                                </span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted text-truncate wa-convo-preview" style="font-size: 0.82rem; max-width: 190px;">
                                    {{ $convo->last_message_preview ?: 'No messages yet' }}
                                </span>

                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    @if ($convo->status === 'resolved')
                                        <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 0.62rem;">Resolved</span>
                                    @endif
                                    <span class="badge rounded-pill bg-success text-white px-1.5 py-0.5 wa-convo-unread-pill {{ $convo->unread_count > 0 ? '' : 'd-none' }}" style="font-size: 0.68rem;">
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
        </aside>

        {{-- Column 2: Active Chat Stream & Composer --}}
        @if ($activeConversation)
            <main class="wa-chat-column" id="waChatMiddleColumn">
                {{-- WhatsApp Chat Header --}}
                <div class="wa-chat-header">
                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                        <a href="{{ route('admin.whatsapp.inbox', ['filter' => $filter, 'search' => $search]) }}" class="btn btn-sm btn-light border rounded-circle wa-action-icon-btn d-md-none me-1" title="Back to conversations">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div class="position-relative flex-shrink-0">
                            <div class="wa-avatar rounded-circle d-flex align-items-center justify-content-center text-white shadow-2xs"
                                 style="background-color: {{ '#' . substr(md5($activeConversation->phone), 0, 6) }}; width: 40px; height: 40px; font-size: 0.95rem;">
                                {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                            </div>
                            @if ($activeConversation->isWindowOpen())
                                <span class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-1" title="24h Service Window Active"></span>
                            @endif
                        </div>
                        <div class="min-w-0 text-truncate">
                            <div class="d-flex align-items-center gap-1.5">
                                <h6 class="fw-bold mb-0 text-dark text-truncate wa-contact-name" style="max-width: 220px; font-size: 0.98rem;">
                                    {{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }}
                                </h6>
                                @if ($customerContext['user'] ?? false)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill py-0 px-1" style="font-size: 0.6rem;" title="Registered Customer">
                                        <i class="bi bi-patch-check-fill"></i>
                                    </span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-0.5 text-nowrap">
                                <button type="button" class="btn btn-link btn-xs p-0 text-muted text-decoration-none d-inline-flex align-items-center gap-1 hover-dark" title="Click to copy phone number" onclick="navigator.clipboard.writeText('+{{ $activeConversation->phone }}'); this.querySelector('.copy-icon').className='bi bi-check text-success copy-icon'; setTimeout(() => this.querySelector('.copy-icon').className='bi bi-copy text-muted copy-icon', 1500);">
                                    <span style="font-size: 0.75rem;">+{{ $activeConversation->phone }}</span>
                                    <i class="bi bi-copy text-muted copy-icon" style="font-size: 0.65rem;"></i>
                                </button>
                                <span class="badge {{ $activeConversation->isWindowOpen() ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' }} rounded-pill"
                                      style="font-size: 0.65rem; padding: 0.15rem 0.5rem;" id="activeWindowBadge">
                                    <i class="bi {{ $activeConversation->isWindowOpen() ? 'bi-clock-fill' : 'bi-lock-fill' }} me-0.5"></i>
                                    <span id="activeWindowLabel">{{ $activeConversation->windowRemainingFormatted() }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Header Action Buttons --}}
                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                        {{-- Agent Assignment Dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 d-flex align-items-center gap-1 shadow-2xs hover-bg-light" type="button" data-bs-toggle="dropdown" id="btnAssignDropdown" title="Assign to team agent" style="font-size: 0.78rem;">
                                <i class="bi bi-person text-secondary"></i>
                                <span id="assignedAgentLabel" class="fw-semibold text-dark d-none d-sm-inline">
                                    {{ $activeConversation->assignedAgent?->name ?? 'Unassigned' }}
                                </span>
                                <i class="bi bi-chevron-down text-muted" style="font-size: 0.55rem;"></i>
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
                            <button class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 d-flex align-items-center gap-1 shadow-2xs hover-bg-light" type="button" data-bs-toggle="dropdown" id="btnStatusDropdown" title="Conversation Status" style="font-size: 0.78rem;">
                                @php
                                    $statusConfig = match($activeConversation->status) {
                                        'resolved' => ['color' => 'secondary', 'icon' => 'bi-check2-all', 'label' => 'Resolved'],
                                        'snoozed' => ['color' => 'warning', 'icon' => 'bi-alarm', 'label' => 'Snoozed'],
                                        default => ['color' => 'success', 'icon' => 'bi-circle-fill', 'label' => 'Open']
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusConfig['color'] }}-subtle text-{{ $statusConfig['color'] }} p-1 rounded-circle">
                                    <i class="bi {{ $statusConfig['icon'] }}" style="font-size: 0.6rem;"></i>
                                </span>
                                <span id="convoStatusLabel" class="fw-semibold text-dark d-none d-sm-inline">{{ $statusConfig['label'] }}</span>
                                <i class="bi bi-chevron-down text-muted" style="font-size: 0.55rem;"></i>
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

                        {{-- Refresh Messages Button --}}
                        <button type="button" class="btn btn-sm btn-white border wa-action-icon-btn rounded-circle shadow-2xs hover-dark" id="btnRefreshChat" title="Refresh messages" style="width: 34px; height: 34px;">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>

                        {{-- Toggle Customer Info Drawer (Desktop & Mobile) --}}
                        <button class="btn btn-sm btn-white border wa-action-icon-btn rounded-circle shadow-2xs hover-dark d-none d-xl-inline-flex" type="button" id="btnToggleContextDesktop" title="Toggle Customer Info Drawer" style="width: 34px; height: 34px;">
                            <i class="bi bi-layout-sidebar-reverse"></i>
                        </button>
                        <button class="btn btn-sm btn-white border wa-action-icon-btn rounded-circle shadow-2xs d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#customerContextOffcanvas" title="Customer Profile" style="width: 34px; height: 34px;">
                            <i class="bi bi-person-badge"></i>
                        </button>
                    </div>
                </div>

                {{-- Window Closed Notice (Meta Policy) --}}
                <div id="waWindowExpiredNotice" class="alert alert-warning border-0 rounded-0 mb-0 py-2 px-3 small d-flex align-items-center justify-content-between flex-shrink-0 {{ $activeConversation->isWindowOpen() ? 'd-none' : '' }}" style="background-color: #fffbeb; border-bottom: 1px solid #fde68a !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                        <span class="text-dark" style="font-size: 0.8rem;"><strong>24-Hour window expired.</strong> Free-form text may fail. Send an approved Meta Template to resume conversation.</span>
                    </div>
                    <button type="button" class="btn btn-warning btn-xs rounded-pill px-3 py-1 shadow-2xs fw-semibold" data-bs-toggle="modal" data-bs-target="#sendTemplateModal">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Send Template
                    </button>
                </div>

                {{-- Scrollable Messages Stream --}}
                <div class="wa-chat-stream" id="waChatStream">
                    @php $lastDate = null; @endphp
                    @forelse ($messages as $msg)
                        @php
                            $msgDate = $msg->created_at->format('Y-m-d');
                            $displayDate = $msg->created_at->isToday() ? 'Today' : ($msg->created_at->isYesterday() ? 'Yesterday' : $msg->created_at->format('d M Y'));
                        @endphp
                        @if ($lastDate !== $msgDate)
                            <div class="d-flex justify-content-center my-2 sticky-top" style="z-index: 2;">
                                <span class="wa-date-divider">
                                    {{ $displayDate }}
                                </span>
                            </div>
                            @php $lastDate = $msgDate; @endphp
                        @endif
                        @include('admin.whatsapp.partials.message-bubble', ['msg' => $msg])
                    @empty
                        <div class="my-auto text-center text-muted p-5" id="waEmptyMessageNotice">
                            <i class="bi bi-chat-heart fs-1 text-success opacity-75 d-block mb-2"></i>
                            <h6 class="fw-bold text-dark">Start WhatsApp Conversation</h6>
                            <p class="small text-muted mb-0">Type a message below or send an approved Meta Template to initiate a dialogue with {{ $activeConversation->contact_name ?: '+' . $activeConversation->phone }}.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Media Attachment Live Preview Capsule --}}
                <div id="waAttachmentPreview" class="d-none px-3 py-2 bg-white border-top d-flex align-items-center justify-content-between flex-shrink-0" style="border-top: 1px solid #d1d7db !important;">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <div id="waAttachmentThumb" class="rounded-3 overflow-hidden bg-light flex-shrink-0 border" style="width: 44px; height: 44px; display: none;">
                            <img src="" class="w-100 h-100 object-fit-cover" alt="preview">
                        </div>
                        <i class="bi bi-file-earmark-arrow-up fs-2 text-primary flex-shrink-0" id="waAttachmentGenericIcon"></i>
                        <div class="min-w-0">
                            <div class="fw-semibold small text-truncate text-dark" id="waAttachmentName" style="max-width: 260px;">file.pdf</div>
                            <small class="text-muted" id="waAttachmentSize" style="font-size: 0.72rem;">0 KB</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-light rounded-circle text-danger p-1" id="waRemoveAttachment">
                        <i class="bi bi-x fs-5"></i>
                    </button>
                </div>

                {{-- Authentic WhatsApp Web Bottom Composer Bar --}}
                <div class="wa-composer-wrapper" id="waComposerWrapper">
                    {{-- Mode Selector Capsule: WhatsApp Public Reply vs Private Internal Note --}}
                    <div class="d-flex align-items-center justify-content-between px-1">
                        <div class="wa-mode-toggle" role="group">
                            <button type="button" class="btn-mode active" id="btnModeReply">
                                <i class="bi bi-whatsapp me-1 text-success"></i> Customer Reply
                            </button>
                            <button type="button" class="btn-mode" id="btnModeNote">
                                <i class="bi bi-lock-fill me-1 text-warning"></i> Internal Note
                            </button>
                        </div>
                        <span class="small text-muted d-none d-sm-inline" style="font-size: 0.72rem;" id="modeHintText">
                            <i class="bi bi-info-circle me-1"></i>Sends message directly to customer's WhatsApp
                        </span>
                    </div>

                    {{-- Main Typing Row (Full width input container + authentic WhatsApp action icons) --}}
                    <form id="waSendForm" class="d-flex align-items-end gap-2 w-100">
                        @csrf
                        <input type="file" id="waFileInput" class="d-none" accept="image/*,application/pdf,.doc,.docx,audio/*,video/*">

                        {{-- Action Icons Left --}}
                        <div class="d-flex align-items-center gap-1 flex-shrink-0 mb-0.5">
                            {{-- Paperclip Attachment Icon --}}
                            <button type="button" class="wa-action-icon-btn" id="btnAttachFile" title="Attach photo, video or document">
                                <i class="bi bi-paperclip"></i>
                            </button>

                            {{-- Quick Reply Snippets Menu --}}
                            <div class="dropdown">
                                <button type="button" class="wa-action-icon-btn text-primary" data-bs-toggle="dropdown" aria-expanded="false" id="btnQuickReplies" title="Quick Reply Snippets (or type / in message)">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-start shadow-lg border-0 rounded-4 p-2 wa-quick-reply-dropdown" style="max-height: 280px; overflow-y: auto; width: 320px; z-index: 1060;" id="waQuickReplyMenu">
                                    <li class="dropdown-header small text-uppercase fw-bold text-muted px-2 py-1">⚡ Quick Reply Snippets</li>
                                    <li class="text-center py-2 text-muted small"><span class="spinner-border spinner-border-sm"></span> Loading snippets...</li>
                                </ul>
                            </div>

                            {{-- Send Approved Meta Template Icon --}}
                            <button type="button" class="wa-action-icon-btn text-warning" data-bs-toggle="modal" data-bs-target="#sendTemplateModal" title="Send Approved Meta Template (Bypasses 24h Window)">
                                <i class="bi bi-shield-check"></i>
                            </button>
                        </div>

                        {{-- Wide Full-Width Typing Capsule --}}
                        <div class="wa-input-container position-relative">
                            {{-- Slash Autocomplete Suggestions --}}
                            <div id="waSlashSuggestions" class="position-absolute bottom-100 start-0 mb-2 w-100 bg-white rounded-3 shadow-lg border p-1 d-none" style="max-height: 200px; overflow-y: auto; z-index: 1050;"></div>

                            <div class="wa-input-capsule">
                                <textarea id="waMessageInput" rows="1"
                                          placeholder="Type a message or type / for quick replies... (Enter to send, Shift+Enter for newline)"></textarea>
                            </div>
                        </div>

                        {{-- WhatsApp Green Circular Send Button --}}
                        <button type="submit" class="wa-send-circle-btn" id="btnSendMessage" title="Send message (Enter)">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </main>

            {{-- Column 3: Customer Context Drawer --}}
            <aside class="wa-context-column d-none d-xl-flex" id="waContextColumn">
                @include('admin.whatsapp.partials.customer-context', ['customerContext' => $customerContext, 'activeConversation' => $activeConversation])
            </aside>

            {{-- Offcanvas Drawer for mobile & tablet screens --}}
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
            {{-- WhatsApp Web Authentic Empty Desktop Splash Screen --}}
            <main class="col d-flex flex-column align-items-center justify-content-center p-5 text-center bg-white" style="background-color: #f0f2f5 !important; border-left: 1px solid #d1d7db;">
                <div class="wa-empty-illustration mb-4 p-4 rounded-circle bg-white text-emerald d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 100px; height: 100px;">
                    <i class="bi bi-whatsapp fs-1" style="color: #25D366;"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">WhatsApp for Web</h4>
                <p class="text-muted small mb-4" style="max-width: 440px; line-height: 1.6; font-size: 0.88rem;">
                    Send and receive messages directly with your customers in real time. Select a conversation from the left directory to start chatting.
                </p>
                <div class="d-inline-flex align-items-center gap-1.5 text-muted px-3 py-1 rounded-pill bg-white border shadow-2xs" style="font-size: 0.75rem;">
                    <i class="bi bi-lock-fill text-muted"></i>
                    <span>End-to-end encrypted with Meta Cloud API</span>
                </div>
            </main>
        @endif

    </div>
</div>

@if ($activeConversation)
<!-- Send Meta Template Modal -->
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-labelledby="sendTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header bg-emerald text-white p-3 px-4 d-flex align-items-center justify-content-between" style="background-color: #1f3b25;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="sendTemplateModalLabel">Send Approved Meta Template</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Bypasses 24-hour service window & initiates customer conversation</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-xs btn-outline-light rounded-pill px-2.5 py-1" id="btnSyncTemplatesLive" title="Sync registered templates directly from Meta WABA">
                        <i class="bi bi-arrow-repeat me-1"></i><span>Sync Meta</span>
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <form id="waSendTemplateForm">
                <div class="modal-body p-4 bg-light-subtle">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-dark">Choose Meta Template</label>
                                <select class="form-select rounded-3" id="tmplSelect" required>
                                    @if(isset($approvedTemplates) && $approvedTemplates->isNotEmpty())
                                        <optgroup label="Synchronized Meta Templates" id="tmplOptGroupSynced">
                                            @foreach($approvedTemplates as $at)
                                                @php
                                                    $vars = $at->variables ?? [];
                                                    $varSummary = !empty($vars) ? ' • ' . '{' . '{' . implode('}' . '}, ' . '{' . '{', $vars) . '}' . '}' : '';
                                                @endphp
                                                <option value="{{ $at->name }}" data-category="{{ $at->category }}" {{ $loop->first ? 'selected' : '' }}>
                                                    {{ $at->name }} ({{ ucfirst($at->category) }}{{ $varSummary }})
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    <optgroup label="Standard Presets">
                                        <option value="welcome_message">🌿 Welcome Message (1 variable: name)</option>
                                        <option value="order_confirmation">📦 Order Confirmation (2 variables: 1, 2)</option>
                                        <option value="checkout_recovery">🛒 Checkout Recovery (1 variable: text + image)</option>
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

                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1.5">
                                    <label class="form-label fw-bold small text-dark mb-0">Template Variables (Params)</label>
                                    <button type="button" class="btn btn-link btn-xs p-0 text-decoration-none fw-semibold text-success" id="btnAddTemplateParam">
                                        <i class="bi bi-plus-circle me-1"></i>Add Variable
                                    </button>
                                </div>
                                <div id="tmplDynamicFields">
                                    <!-- Dynamically populated parameter fields -->
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Number of variables must match what was approved on Meta for this template.</small>
                            </div>

                            <div class="mb-3" id="tmplHeaderImageGroup">
                                <label class="form-label fw-bold small text-dark" id="tmplHeaderImageLabel">Header Image URL (Optional)</label>
                                <input type="url" class="form-control rounded-3" id="tmplHeaderImageUrl" placeholder="{{ asset('images/logo.png') }}">
                                <small class="text-muted d-block mt-0.5" id="tmplHeaderImageHelp" style="font-size: 0.72rem;">Only needed if your template requires a Media Image Header.</small>
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
                                    <div class="text-muted small mt-2 d-none" id="previewFooterText" style="font-size: 0.72rem;"></div>
                                    <div class="text-end text-muted mt-2" style="font-size: 0.65rem;">Now • <i class="bi bi-check2 text-muted"></i></div>
                                </div>
                                <div id="previewButtons" class="mt-2 d-flex flex-column gap-1.5 w-100"></div>
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
            btnModeReply.classList.add('active');
            btnModeNote.classList.remove('active', 'note-active');
            composerWrapper.classList.remove('internal-note-mode');
            composerWrapper.style.backgroundColor = '#f0f2f5';
            modeHintText.innerHTML = '<i class="bi bi-info-circle me-1"></i>Sends message directly to customer\'s WhatsApp';
            messageInput.placeholder = 'Type a message or type / for quick replies... (Enter to send, Shift+Enter for newline)';
            btnAttachFile.classList.remove('d-none');
            const btnSend = document.getElementById('btnSendMessage');
            btnSend.style.backgroundColor = '#25D366';
            btnSend.style.borderColor = '#25D366';
            btnSend.innerHTML = '<i class="bi bi-send-fill text-white fs-6"></i>';
        });

        btnModeNote.addEventListener('click', () => {
            currentComposerMode = 'note';
            btnModeNote.classList.add('note-active');
            btnModeReply.classList.remove('active');
            composerWrapper.classList.add('internal-note-mode');
            composerWrapper.style.backgroundColor = '#fffdf5';
            modeHintText.innerHTML = '<strong style="color: #92400e;"><i class="bi bi-lock-fill"></i> Internal Staff Note:</strong> Visible to team only. Customer will NOT receive this.';
            messageInput.placeholder = 'Type an internal team note... (Enter to save)';
            btnAttachFile.classList.add('d-none');
            const btnSend = document.getElementById('btnSendMessage');
            btnSend.style.backgroundColor = '#f59e0b';
            btnSend.style.borderColor = '#f59e0b';
            btnSend.innerHTML = '<i class="bi bi-sticky-fill text-white fs-6"></i>';
        });
    }

    // Toggle Desktop Customer Context Panel
    const btnToggleContextDesktop = document.getElementById('btnToggleContextDesktop');
    const waContextColumn = document.getElementById('waContextColumn');
    if (btnToggleContextDesktop && waContextColumn) {
        btnToggleContextDesktop.addEventListener('click', () => {
            if (waContextColumn.classList.contains('d-none')) {
                waContextColumn.classList.remove('d-none');
                btnToggleContextDesktop.classList.remove('text-primary');
            } else {
                waContextColumn.classList.add('d-none');
                btnToggleContextDesktop.classList.add('text-primary');
            }
        });
    }

    function scrollToBottom(smooth = false) {
        if (stream) {
            if (smooth) {
                stream.scrollTo({ top: stream.scrollHeight, behavior: 'smooth' });
            } else {
                stream.scrollTop = stream.scrollHeight;
            }
        }
    }
    scrollToBottom();
    requestAnimationFrame(() => scrollToBottom());
    setTimeout(() => scrollToBottom(), 120);
    setTimeout(() => scrollToBottom(), 350);

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
        div.className = 'd-flex justify-content-end mb-2 px-1';
        div.setAttribute('id', `msg-${msg.id}`);
        div.setAttribute('data-message-id', msg.id);

        let content = '';
        if (msg.message_type === 'image') {
            const url = msg.media_url ? '{{ asset('storage') }}/' + msg.media_url : '#';
            content = `<div class="mb-1.5 overflow-hidden rounded-3"><img src="${url}" class="rounded-3 img-fluid w-100 shadow-2xs" style="max-height: 240px; object-fit: cover;" alt="Photo"></div>`;
            if (msg.body) content += `<div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body)}</div>`;
        } else if (msg.message_type === 'document') {
            content = `<div class="p-2.5 rounded-3 bg-white d-flex align-items-center gap-2 mb-1.5 border shadow-2xs">
                <i class="bi bi-file-earmark-pdf fs-4 text-danger flex-shrink-0"></i>
                <div class="text-truncate fw-semibold small text-dark" style="max-width: 180px;">${escapeHtml(msg.media_filename || 'Document')}</div>
            </div>`;
            if (msg.body) content += `<div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body)}</div>`;
        } else if (msg.message_type === 'template') {
            const header = msg.media_url ? `<div class="mb-1.5 overflow-hidden rounded-3"><img src="${msg.media_url}" class="rounded-3 img-fluid w-100 shadow-2xs" style="max-height: 200px; object-fit: cover;"></div>` : '';
            content = `${header}<div class="mb-1.5"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i>Approved Meta Template</span></div><div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body || '')}</div>`;
        } else {
            content = `<div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body || '')}</div>`;
        }

        div.innerHTML = `
            <div class="wa-bubble wa-bubble-outbound p-2.5 px-3">
                ${content}
                <div class="d-flex align-items-center justify-content-end gap-1 mt-1 text-muted" style="font-size: 0.68rem; opacity: 0.85;">
                    <span>${time}</span>
                    <span class="message-status-tick ms-0.5">
                        <i class="bi bi-check2 text-muted" title="Sent to Meta"></i>
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
                    wrapper.className = `d-flex ${isOut ? 'justify-content-end' : 'justify-content-start'} mb-2 px-1`;
                    wrapper.setAttribute('id', `msg-${msg.id}`);
                    wrapper.setAttribute('data-message-id', msg.id);

                    let bodyHtml = '';
                    if (msg.message_type === 'image' && msg.media_url) {
                        bodyHtml = `<div class="mb-1.5 overflow-hidden rounded-3"><img src="${msg.media_url}" class="rounded-3 img-fluid w-100 shadow-2xs" style="max-height: 240px; object-fit: cover;"></div>`;
                        if (msg.body) bodyHtml += `<div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body)}</div>`;
                    } else if (msg.message_type === 'document') {
                        bodyHtml = `<div class="p-2.5 rounded-3 bg-white d-flex align-items-center gap-2 mb-1.5 border shadow-2xs">
                            <i class="bi bi-file-earmark-pdf fs-4 text-danger flex-shrink-0"></i>
                            <div class="text-truncate fw-semibold small text-dark" style="max-width: 180px;">${escapeHtml(msg.media_filename || 'Document')}</div>
                            <a href="${msg.media_url || '#'}" target="_blank" class="btn btn-sm btn-light border rounded-circle p-1 ms-auto" style="width: 30px; height: 30px;"><i class="bi bi-download text-dark"></i></a>
                        </div>`;
                        if (msg.body) bodyHtml += `<div class="mb-1 text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body)}</div>`;
                    } else if (msg.message_type === 'template') {
                        const header = msg.media_url ? `<div class="mb-1.5 overflow-hidden rounded-3"><img src="${msg.media_url}" class="rounded-3 img-fluid w-100 shadow-2xs" style="max-height: 200px; object-fit: cover;"></div>` : '';
                        bodyHtml = `${header}<div class="mb-1.5"><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill fw-semibold" style="font-size: 0.65rem;"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i>Approved Meta Template</span></div><div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body || '')}</div>`;
                    } else {
                        bodyHtml = `<div class="text-break" style="font-size: 0.88rem; line-height: 1.45;">${escapeHtml(msg.body || '')}</div>`;
                    }

                    const tick = msg.status === 'read' ? '<i class="bi bi-check2-all" style="color: #53bdeb; font-weight: 800;" title="Read by customer"></i>' :
                                 (msg.status === 'delivered' ? '<i class="bi bi-check2-all text-muted" title="Delivered to customer phone"></i>' :
                                 (msg.status === 'failed' ? '<i class="bi bi-exclamation-circle-fill text-danger" title="Delivery failed"></i>' :
                                 '<i class="bi bi-check2 text-muted" title="Sent to Meta"></i>'));

                    wrapper.innerHTML = `
                        <div class="wa-bubble ${isOut ? 'wa-bubble-outbound' : 'wa-bubble-inbound'} p-2.5 px-3">
                            ${bodyHtml}
                            <div class="d-flex align-items-center ${isOut ? 'justify-content-end' : 'justify-content-start'} gap-1 mt-1 text-muted" style="font-size: 0.68rem; opacity: 0.85;">
                                <span>${msg.created_at_time}</span>
                                ${isOut ? `<span class="message-status-tick ms-0.5">${tick}</span>` : ''}
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
                                tickSpan.innerHTML = '<i class="bi bi-check2-all" style="color: #53bdeb; font-weight: 800;" title="Read by customer"></i>';
                            } else if (item.status === 'delivered') {
                                tickSpan.innerHTML = '<i class="bi bi-check2-all text-muted" title="Delivered to customer phone"></i>';
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
        welcome_message: {
            title: "Welcome to VANRITI 🌿",
            text: "Hi " + "{" + "{name}" + "}" + ",\n\nWe're happy to have you with us!\n\nEnjoy ₹100 OFF on your first order of ₹499 or more, plus FREE SHIPPING.\n\nUse Code: FIRST100",
            fields: [
                { id: "p_name", name: "name", token: "{" + "{name}" + "}", label: "{" + "{name}" + "}", default: customerName },
            ],
            language: "en"
        },
        order_confirmation: {
            title: "Order Confirmed 📦",
            text: "Hi " + "{" + "{1}" + "}" + ",\nYour order has been successfully placed and is being processed. Your order number is " + "{" + "{2}" + "}" + ". You can view order details below.",
            fields: [
                { id: "p1", name: "1", token: "{" + "{1}" + "}", label: "{" + "{1}" + "}", default: customerName },
                { id: "p2", name: "2", token: "{" + "{2}" + "}", label: "{" + "{2}" + "}", default: recentOrderNumber },
            ],
            language: "en_US"
        },
        checkout_recovery: {
            title: "Checkout Recovery 🛒",
            text: "Hi Greetings From VANRITI !! \n\nWe noticed you did not finish your order for \n\n" + "{" + "{text}" + "}" + "\n\nYour items are reserved for a short time.",
            header_type: "IMAGE",
            fields: [
                { id: "p_text", name: "text", token: "{" + "{text}" + "}", label: "{" + "{text}" + "}", default: "Selected Items" },
            ],
            language: "en"
        },
        otp_verification: {
            title: "Verification Code 🔐",
            text: "*" + "{" + "{1}" + "}" + "* is your verification code. For your security, do not share this code.",
            fields: [
                { id: "p1", name: "1", token: "{" + "{1}" + "}", label: "{" + "{1}" + "}", default: "123456" },
            ],
            language: "en"
        },
        promotional_offer: {
            title: "Special VIP Offer 🎉",
            text: "Hello " + "{" + "{1}" + "}" + ", celebrate with us! Enjoy " + "{" + "{2}" + "}" + " on your next order using coupon code: " + "{" + "{3}" + "}" + " at checkout.",
            fields: [
                { id: "p1", name: "1", token: "{" + "{1}" + "}", label: "{" + "{1}" + "}", default: customerName },
                { id: "p2", name: "2", token: "{" + "{2}" + "}", label: "{" + "{2}" + "}", default: "Flat 15% OFF" },
                { id: "p3", name: "3", token: "{" + "{3}" + "}", label: "{" + "{3}" + "}", default: "FESTIVE15" },
            ],
            language: "en"
        },
        custom: {
            title: "Custom Meta Template",
            text: "Template variables: " + "{" + "{1}" + "}" + ", " + "{" + "{2}" + "}",
            fields: [
                { id: "p1", name: "1", token: "{" + "{1}" + "}", label: "{" + "{1}" + "}", default: customerName },
                { id: "p2", name: "2", token: "{" + "{2}" + "}", label: "{" + "{2}" + "}", default: "" },
            ],
            language: "en"
        }
    };

    function extractTemplateTokens(bodyText) {
        if (!bodyText || typeof bodyText !== 'string') return [];
        const tokens = [];
        const openDelimiter = '{' + '{';
        const closeDelimiter = '}' + '}';
        const parts = bodyText.split(openDelimiter);
        for (let i = 1; i < parts.length; i++) {
            const closeIdx = parts[i].indexOf(closeDelimiter);
            if (closeIdx !== -1) {
                const token = parts[i].substring(0, closeIdx).trim();
                if (token && !tokens.includes(token)) {
                    tokens.push(token);
                }
            }
        }
        return tokens;
    }

    function buildFieldsForTokens(tokens) {
        if (!Array.isArray(tokens)) return [];
        return tokens.map((tokenKey, idx) => {
            const tokenBadge = '{' + '{' + tokenKey + '}' + '}';
            let defVal = '';
            const lower = String(tokenKey).toLowerCase();
            if (tokenKey === '1' || lower.includes('name')) {
                defVal = customerName;
            } else if (tokenKey === '2' || lower.includes('order')) {
                defVal = recentOrderNumber;
            } else if (lower.includes('text') || lower.includes('item') || lower.includes('cart') || lower.includes('product')) {
                defVal = 'Selected Items';
            }
            return {
                id: "p_" + tokenKey,
                name: String(tokenKey),
                token: tokenBadge,
                label: tokenBadge,
                default: defVal
            };
        });
    }

    // Hydrate synchronized Meta templates from backend
    const approvedMetaTemplates = @json($approvedTemplates ?? []);
    if (Array.isArray(approvedMetaTemplates)) {
        approvedMetaTemplates.forEach(tpl => {
            const tokens = (Array.isArray(tpl.variables) && tpl.variables.length > 0)
                ? tpl.variables
                : extractTemplateTokens(tpl.body_text || '');
            const fields = buildFieldsForTokens(tokens);

            templateSchemas[tpl.name] = {
                title: tpl.header_text || (tpl.name.replace(/_/g, ' ').toUpperCase()),
                text: tpl.body_text || '',
                footer: tpl.footer_text || '',
                buttons: tpl.buttons || [],
                header_type: tpl.header_type || 'NONE',
                fields: fields,
                language: tpl.language || 'en'
            };
        });
    }

    function renderTemplateFields() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.order_confirmation;

        if (schema && schema.language) {
            const langInput = document.getElementById('tmplLangCode');
            if (langInput) langInput.value = schema.language;
        }

        if (customTmplGroup) {
            if (key === 'custom') {
                customTmplGroup.classList.remove('d-none');
                if (customTmplInput) customTmplInput.required = true;
            } else {
                customTmplGroup.classList.add('d-none');
                if (customTmplInput) customTmplInput.required = false;
            }
        }

        const headerImgGroup = document.getElementById('tmplHeaderImageGroup');
        const headerImgInput = document.getElementById('tmplHeaderImageUrl');
        const headerImgLabel = document.getElementById('tmplHeaderImageLabel');
        const headerImgHelp = document.getElementById('tmplHeaderImageHelp');
        const defaultLogoUrl = '{{ asset('images/logo.png') }}';

        if (schema && schema.header_type === 'IMAGE') {
            if (headerImgLabel) headerImgLabel.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-image me-1"></i>Header Image URL (Required by Meta)</span>';
            if (headerImgHelp) headerImgHelp.innerHTML = 'This template requires an image header. A default logo URL has been prefilled.';
            if (headerImgInput && !headerImgInput.value.trim()) {
                headerImgInput.value = defaultLogoUrl;
            }
        } else {
            if (headerImgLabel) headerImgLabel.textContent = 'Header Image URL (Optional)';
            if (headerImgHelp) headerImgHelp.textContent = 'Only needed if your template requires a Media Image Header.';
            if (headerImgInput && headerImgInput.value === defaultLogoUrl) {
                headerImgInput.value = '';
            }
        }

        if (tmplDynamicFields) {
            if (schema && schema.fields && schema.fields.length > 0) {
                tmplDynamicFields.innerHTML = schema.fields.map((f, idx) => {
                    const tokenStr = f.token || ('{' + '{' + (f.name || (idx + 1)) + '}' + '}');
                    const fieldName = escapeHtml(f.name || String(idx + 1));
                    return `
                        <div class="mb-2 input-group input-group-sm tmpl-param-row">
                            <span class="input-group-text bg-white fw-bold text-muted font-monospace tmpl-param-badge" style="min-width: 90px;">` + escapeHtml(tokenStr) + `</span>
                            <input type="text" class="form-control tmpl-param-input" data-name="` + fieldName + `" data-index="` + (idx + 1) + `" value="` + escapeHtml(f.default || '') + `" placeholder="Value for ` + escapeHtml(tokenStr) + `" required>
                            <button type="button" class="btn btn-outline-danger btn-remove-param" title="Remove this variable"><i class="bi bi-x-lg"></i></button>
                        </div>
                    `;
                }).join('');
            } else {
                tmplDynamicFields.innerHTML = `
                    <div class="alert alert-light border small text-muted py-2 mb-2" id="tmplNoParamsAlert">
                        <i class="bi bi-info-circle me-1 text-primary"></i>No dynamic variables required for this template.
                    </div>
                `;
            }

            attachParamListeners();
        }

        updateTemplatePreview();
    }

    function renumberParamRows() {
        if (!tmplDynamicFields) return;
        const rows = tmplDynamicFields.querySelectorAll('.tmpl-param-row');
        rows.forEach((row, idx) => {
            const badge = row.querySelector('.tmpl-param-badge');
            const input = row.querySelector('.tmpl-param-input');
            const tokenStr = '{' + '{' + (input?.dataset.name || (idx + 1)) + '}' + '}';
            if (badge) badge.textContent = tokenStr;
            if (input) {
                input.dataset.index = idx + 1;
                input.placeholder = 'Value for ' + tokenStr;
            }
        });
        if (rows.length === 0) {
            tmplDynamicFields.innerHTML = `
                <div class="alert alert-light border small text-muted py-2 mb-2" id="tmplNoParamsAlert">
                    <i class="bi bi-info-circle me-1 text-primary"></i>No dynamic variables required for this template.
                </div>
            `;
        }
        updateTemplatePreview();
    }

    function attachParamListeners() {
        if (!tmplDynamicFields) return;
        tmplDynamicFields.querySelectorAll('.tmpl-param-input').forEach(inp => {
            inp.removeEventListener('input', updateTemplatePreview);
            inp.addEventListener('input', updateTemplatePreview);
        });
        tmplDynamicFields.querySelectorAll('.btn-remove-param').forEach(btn => {
            btn.onclick = function() {
                this.closest('.tmpl-param-row')?.remove();
                renumberParamRows();
            };
        });
    }

    const btnAddTemplateParam = document.getElementById('btnAddTemplateParam');
    if (btnAddTemplateParam) {
        btnAddTemplateParam.addEventListener('click', function () {
            if (!tmplDynamicFields) return;
            const noAlert = document.getElementById('tmplNoParamsAlert');
            if (noAlert) noAlert.remove();

            const currentCount = tmplDynamicFields.querySelectorAll('.tmpl-param-row').length;
            const newIndex = currentCount + 1;
            const tokenStr = '{' + '{' + newIndex + '}' + '}';
            const div = document.createElement('div');
            div.className = 'mb-2 input-group input-group-sm tmpl-param-row';
            div.innerHTML = `
                <span class="input-group-text bg-white fw-bold text-muted font-monospace tmpl-param-badge" style="min-width: 90px;">` + escapeHtml(tokenStr) + `</span>
                <input type="text" class="form-control tmpl-param-input" data-index="` + newIndex + `" value="" placeholder="Value for ` + escapeHtml(tokenStr) + `" required>
                <button type="button" class="btn btn-outline-danger btn-remove-param" title="Remove this variable"><i class="bi bi-x-lg"></i></button>
            `;
            tmplDynamicFields.appendChild(div);
            attachParamListeners();
            updateTemplatePreview();
        });
    }

    function updateTemplatePreview() {
        if (!tmplSelect) return;
        const key = tmplSelect.value;
        const schema = templateSchemas[key] || templateSchemas.order_confirmation;
        if (!schema) return;

        if (previewTitle) {
            if (key === 'custom' && customTmplInput && customTmplInput.value.trim()) {
                previewTitle.textContent = customTmplInput.value.trim().toUpperCase();
            } else {
                previewTitle.textContent = schema.title || 'VANRITI';
            }
        }

        let body = schema.text || '';
        const inputs = tmplDynamicFields ? tmplDynamicFields.querySelectorAll('.tmpl-param-input') : [];
        inputs.forEach((inp, idx) => {
            const pName = inp.dataset.name || '';
            const val = inp.value || '';
            if (pName) {
                const namedDouble = '{' + '{' + pName + '}' + '}';
                const namedSingle = '{' + pName + '}';
                body = body.split(namedDouble).join(val || namedDouble).split(namedSingle).join(val || namedDouble);
            }
            const tokenDouble = '{' + '{' + (idx + 1) + '}' + '}';
            const tokenSingle = '{' + (idx + 1) + '}';
            body = body.split(tokenDouble).join(val || tokenDouble).split(tokenSingle).join(val || tokenDouble);
        });

        if (previewBodyText) {
            previewBodyText.innerHTML = escapeHtml(body).replace(/\n/g, '<br>');
        }

        const previewFooter = document.getElementById('previewFooterText');
        if (previewFooter) {
            if (schema.footer) {
                previewFooter.textContent = schema.footer;
                previewFooter.classList.remove('d-none');
            } else {
                previewFooter.classList.add('d-none');
            }
        }

        const previewButtonsContainer = document.getElementById('previewButtons');
        if (previewButtonsContainer) {
            previewButtonsContainer.innerHTML = '';
            if (Array.isArray(schema.buttons) && schema.buttons.length > 0) {
                previewButtonsContainer.innerHTML = schema.buttons.map(b => {
                    const text = typeof b === 'object' ? (b.text || b.url || 'Action') : b;
                    return `<div class="wa-btn-preview text-center py-1.5 px-3 rounded-3 bg-white border shadow-2xs fw-semibold text-primary" style="font-size: 0.78rem;"><i class="bi bi-box-arrow-up-right me-1"></i>${escapeHtml(text)}</div>`;
                }).join('');
            }
        }

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

    if (customTmplInput) {
        customTmplInput.addEventListener('input', updateTemplatePreview);
    }

    if (tmplHeaderImageUrl) {
        tmplHeaderImageUrl.addEventListener('input', updateTemplatePreview);
    }

    // Live Sync Templates from Meta Button Handler
    const btnSyncLive = document.getElementById('btnSyncTemplatesLive');
    if (btnSyncLive) {
        btnSyncLive.addEventListener('click', async function () {
            btnSyncLive.disabled = true;
            btnSyncLive.innerHTML = '<span class="spinner-border spinner-border-sm text-white" role="status"></span> Syncing...';
            try {
                const response = await fetch('{{ route('admin.whatsapp.templates.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const res = await response.json();
                if (res.success && Array.isArray(res.templates)) {
                    let optGroup = document.getElementById('tmplOptGroupSynced');
                    if (!optGroup) {
                        optGroup = document.createElement('optgroup');
                        optGroup.id = 'tmplOptGroupSynced';
                        optGroup.label = 'Synchronized Meta Templates';
                        tmplSelect.insertBefore(optGroup, tmplSelect.firstChild);
                    }
                    optGroup.innerHTML = '';
                    res.templates.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.name;
                        const tokens = (Array.isArray(t.variables) && t.variables.length > 0)
                            ? t.variables
                            : extractTemplateTokens(t.body_text || '');
                        const fields = buildFieldsForTokens(tokens);
                        const varInfo = tokens.length > 0 ? ' • {{' + tokens.join('}}, {{') + '}}' : '';
                        opt.textContent = `${t.name} (${t.category}${varInfo})`;
                        optGroup.appendChild(opt);

                        templateSchemas[t.name] = {
                            title: t.header_text || (t.name.replace(/_/g, ' ').toUpperCase()),
                            text: t.body_text || '',
                            footer: t.footer_text || '',
                            buttons: t.buttons || [],
                            header_type: t.header_type || 'NONE',
                            fields: fields,
                            language: t.language || 'en'
                        };
                    });

                    alert(`Successfully refreshed ${res.templates.length} templates directly from Meta!`);
                    renderTemplateFields();
                } else {
                    alert('Meta Sync Note: ' + (res.message || 'Could not sync from Meta. Check your WABA ID in Settings.'));
                }
            } catch (err) {
                console.error('Meta live sync error:', err);
                alert('Network or server error while communicating with Meta.');
            } finally {
                btnSyncLive.disabled = false;
                btnSyncLive.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i><span>Sync Meta</span>';
            }
        });
    }

    const sendTemplateModalEl = document.getElementById('sendTemplateModal');
    if (sendTemplateModalEl) {
        sendTemplateModalEl.addEventListener('show.bs.modal', function () {
            renderTemplateFields();
        });
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
                    const rawVal = inp.value.trim();
                    const finalVal = rawVal !== '' ? rawVal : '-';
                    const pName = inp.dataset.name || '';
                    if (pName && isNaN(pName)) {
                        params.push({ name: pName, value: finalVal });
                    } else {
                        params.push(finalVal);
                    }
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
