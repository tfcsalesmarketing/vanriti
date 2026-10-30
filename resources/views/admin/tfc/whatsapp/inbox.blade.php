@extends('admin.layouts.app')

@section('title', 'TFC WhatsApp Live Chat Inbox')

@push('styles')
<style>
/* Full Bleed Neat & Clean WhatsApp Experience matching VANRITI */
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
    transition: all 0.15s ease;
    text-decoration: none;
    flex-shrink: 0;
}
.wa-btn-new-broadcast:hover {
    background: #d1f2e2;
    color: #0d5f3a;
    border-color: #a3e2c0;
}

/* 2. Main Workspace Layout (3 Floating Cards) */
.wa-workspace {
    flex: 1 1 0;
    min-height: 0;
    display: flex;
    gap: 12px;
    width: 100%;
    overflow: hidden;
    position: relative;
}

/* Column 1: Directory */
.wa-sidebar-card {
    width: 300px;
    min-width: 270px;
    max-width: 320px;
    height: 100%;
    min-height: 0;
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #eef2f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    flex-shrink: 0;
}
@media (max-width: 991.98px) {
    .wa-sidebar-card {
        width: 100%;
        max-width: 100%;
    }
}
.wa-search-section {
    padding: 12px 14px 8px 14px;
    background: #ffffff;
    flex-shrink: 0;
}
.wa-search-pill {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    display: flex;
    align-items: center;
    padding: 0 14px;
    height: 38px;
    gap: 8px;
    transition: border-color 0.15s ease;
}
.wa-search-pill:focus-within {
    border-color: #107548;
    background: #ffffff;
}
.wa-search-pill input {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    font-size: 13.5px;
    color: #0f172a;
}
.wa-search-pill input::placeholder {
    color: #94a3b8;
}
.wa-filter-tabs {
    display: flex;
    gap: 6px;
    padding: 6px 12px 10px 12px;
    overflow-x: auto;
    scrollbar-width: none;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    flex-shrink: 0;
    -ms-overflow-style: none;
}
.wa-filter-tabs::-webkit-scrollbar {
    display: none;
}
.wa-filter-chip {
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    color: #64748b;
    background: #f1f5f9;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s ease;
}
.wa-filter-chip.active {
    background: #107548 !important;
    color: #ffffff !important;
    font-weight: 600;
}
.wa-filter-chip:hover:not(.active) {
    background: #e2e8f0;
    color: #0f172a;
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
    padding: 12px 14px;
    border-bottom: 1px solid #f8fafc;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.15s ease;
    position: relative;
}
.wa-convo-item:hover {
    background: #f8fafc;
}
.wa-convo-item.active {
    background: #f1f5f9;
    border-left: 3.5px solid #107548;
}
.wa-avatar {
    width: 42px;
    height: 42px;
    font-size: 1rem;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    flex-shrink: 0;
}

/* Column 2: Chat Canvas */
.wa-chat-card {
    flex: 1 1 0;
    min-width: 0;
    height: 100%;
    min-height: 0;
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #eef2f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.wa-chat-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 10px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    height: 64px;
    z-index: 10;
}
.wa-chat-stream {
    flex: 1 1 0;
    min-height: 0;
    overflow-y: auto;
    background-color: #f7f9fa;
    background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23000000' fill-opacity='0.02' fill-rule='evenodd'%3E%3Cpath d='M11 14.01V7a1 1 0 0 1 2 0v7.01A5.001 5.001 0 0 1 17 19v1a1 1 0 0 1-2 0v-1a3 3 0 0 0-3-3h-1.5A3.5 3.5 0 0 1 7 12.5V11a1 1 0 0 1 2 0v1.5a1.5 1.5 0 0 0 1.5 1.5H11zM65 41a5 5 0 1 1-10 0 5 5 0 0 1 10 0zm-2 0a3 3 0 1 0-6 0 3 3 0 0 0 6 0zM22 66a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm-1 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0zm53-24a1 1 0 0 1 1 1v1.5a3.5 3.5 0 0 1-3.5 3.5H71a3 3 0 0 0-3 3v1a1 1 0 0 1-2 0v-1a5.001 5.001 0 0 1 4-4.99V43a1 1 0 0 1 1-1h2.5a1.5 1.5 0 0 0 1.5-1.5V39a1 1 0 0 1 1-1zm-48-9a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm-2 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0z'/%3E%3C/g%3E%3C/svg%3E");
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    scroll-behavior: smooth;
}
.wa-bubble {
    max-width: 68%;
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    word-break: break-word;
    font-size: 0.88rem;
    line-height: 1.45;
}
.wa-bubble-inbound {
    background-color: #ffffff;
    border-radius: 12px 12px 12px 2px;
    color: #0f172a;
    border: 1px solid #f1f5f9;
}
.wa-bubble-outbound {
    background-color: #e7f8f0;
    border-radius: 12px 12px 2px 12px;
    color: #0f172a;
}

/* Composer Dock */
.wa-composer-wrapper {
    background: #ffffff;
    border-top: 1px solid #f1f5f9;
    padding: 8px 16px 12px 16px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 5;
}
.wa-composer-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.wa-composer-icon:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.wa-input-capsule {
    background: #ffffff;
    border: 1px solid #dcdfe4;
    border-radius: 24px;
    padding: 8px 16px;
    min-height: 44px;
    display: flex;
    align-items: center;
    position: relative;
    width: 100%;
}
.wa-input-capsule:focus-within {
    border-color: #107548;
}
.wa-input-capsule textarea {
    border: none;
    background: transparent;
    width: 100%;
    outline: none;
    resize: none;
    font-size: 13.5px;
    max-height: 100px;
    line-height: 1.4;
    color: #0f172a;
}
.wa-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: #107548;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}
.wa-send-btn:hover {
    background: #0d5f3a;
    transform: scale(1.05);
}

/* Column 3: Context / Direct-Selling Drawer */
.wa-context-card {
    width: 290px;
    min-width: 260px;
    max-width: 310px;
    height: 100%;
    min-height: 0;
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #eef2f5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
}
.wa-convo-list::-webkit-scrollbar,
.wa-chat-stream::-webkit-scrollbar,
.wa-context-card::-webkit-scrollbar {
    width: 5px;
}
.wa-convo-list::-webkit-scrollbar-thumb,
.wa-chat-stream::-webkit-scrollbar-thumb,
.wa-context-card::-webkit-scrollbar-thumb {
    background-color: rgba(0,0,0,0.12);
    border-radius: 9999px;
}
</style>
@endpush

@section('content')
<div class="whatsapp-app-container">

    {{-- 1. Top Executive Operational Bar --}}
    <header class="wa-kpi-bar" id="waOperationalToolbar">
        {{-- Card 1: WhatsApp Inbox (Active) --}}
        <div class="wa-kpi-card active" title="Active TFC WhatsApp Inbox">
            <div class="wa-kpi-icon">
                <i class="bi bi-whatsapp"></i>
            </div>
            <div>
                <div class="wa-kpi-title">
                    <span>TFC WhatsApp Inbox</span>
                    <span class="badge rounded-pill bg-success text-white px-1.5 py-0.5" style="font-size: 0.68rem;">
                        {{ $conversations->total() ?: count($conversations) }}
                    </span>
                </div>
                <div class="wa-kpi-subtitle">
                    <span>{{ $activeWindowsCount }}</span> active windows (24h)
                </div>
            </div>
        </div>

        {{-- Card 2: Live Sync --}}
        <div class="wa-kpi-card" title="Real-time Webhook Active">
            <div class="wa-kpi-icon">
                <i class="bi bi-arrow-repeat text-dark"></i>
            </div>
            <div>
                <div class="wa-kpi-title">Live Sync</div>
                <div class="wa-kpi-subtitle text-muted">Connected</div>
            </div>
        </div>

        {{-- Card 3: Meta Cloud API --}}
        <div class="wa-kpi-card" title="Meta Cloud API Health">
            <div class="wa-kpi-icon">
                <i class="bi bi-cloud text-dark"></i>
            </div>
            <div>
                <div class="wa-kpi-title">Meta Cloud API</div>
                <div class="wa-kpi-subtitle">
                    @if ($isConfigured)
                        <span class="text-success fw-semibold">● Connected</span>
                    @else
                        <a href="{{ route('admin.tfc.whatsapp.profile') }}" class="text-warning text-decoration-none">● Setup Required</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 4: Direct Selling Campaigns --}}
        <a href="{{ route('admin.tfc.whatsapp.campaigns.index') }}" class="wa-kpi-card" title="Direct Seller Broadcast Campaigns">
            <div class="wa-kpi-icon">
                <i class="bi bi-megaphone text-dark"></i>
            </div>
            <div>
                <div class="wa-kpi-title">Campaigns</div>
                <div class="wa-kpi-subtitle">Broadcasts</div>
            </div>
        </a>

        {{-- Card 5: Meta Templates --}}
        <a href="{{ route('admin.tfc.whatsapp.templates.index') }}" class="wa-kpi-card" title="Meta Approved Message Templates">
            <div class="wa-kpi-icon">
                <i class="bi bi-file-earmark-text text-dark"></i>
            </div>
            <div>
                <div class="wa-kpi-title">Templates</div>
                <div class="wa-kpi-subtitle">Manage templates</div>
            </div>
        </a>

        {{-- Card 6: Bot & Quick Replies --}}
        <a href="{{ route('admin.tfc.whatsapp.bot.index') }}" class="wa-kpi-card" title="Auto-Responders & Quick Replies">
            <div class="wa-kpi-icon">
                <i class="bi bi-robot text-dark"></i>
            </div>
            <div>
                <div class="wa-kpi-title">Auto-Replies</div>
                <div class="wa-kpi-subtitle">Bot &amp; Quick replies</div>
            </div>
        </a>

        {{-- Action Button: + New Broadcast --}}
        <a href="{{ route('admin.tfc.whatsapp.campaigns.create') }}" class="wa-btn-new-broadcast ms-auto" title="Create New Broadcast">
            <i class="bi bi-plus-lg"></i>
            <span>New Broadcast</span>
        </a>
    </header>

    {{-- 2. Main 3-Column Floating Cards Grid --}}
    <div class="wa-workspace">

        {{-- Column 1: Conversations Directory Card --}}
        <aside class="wa-sidebar-card {{ $activeConversation ? 'd-none d-md-flex' : '' }}" id="waSidebarColumn">
            {{-- Search Bar --}}
            <div class="wa-search-section">
                <form method="GET" action="{{ route('admin.tfc.whatsapp.inbox') }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="wa-search-pill">
                        <i class="bi bi-search text-muted" style="font-size: 0.88rem;"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search chats or phone...">
                        @if ($search)
                            <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => $filter]) }}" class="text-muted text-decoration-none">&times;</a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Filter Tabs --}}
            <div class="wa-filter-tabs">
                <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'tfc', 'search' => $search]) }}"
                   class="wa-filter-chip {{ $filter === 'tfc' ? 'active' : '' }}">
                    All ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'unread', 'search' => $search]) }}"
                   class="wa-filter-chip {{ $filter === 'unread' ? 'active' : '' }}">
                    Unread @if($unreadCount > 0)<span class="badge bg-danger rounded-pill">{{ $unreadCount }}</span>@endif
                </a>
                <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'inquiry', 'search' => $search]) }}"
                   class="wa-filter-chip {{ $filter === 'inquiry' ? 'active' : '' }}">
                    Inquiries ({{ $stats['inquiries'] }})
                </a>
                <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'grievance', 'search' => $search]) }}"
                   class="wa-filter-chip {{ $filter === 'grievance' ? 'active' : '' }}">
                    Grievances ({{ $stats['grievances'] }})
                </a>
                <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => 'resolved', 'search' => $search]) }}"
                   class="wa-filter-chip {{ $filter === 'resolved' ? 'active' : '' }}">
                    Resolved
                </a>
            </div>

            {{-- Conversation List Items --}}
            <div class="wa-convo-list" id="waConvoListContainer">
                @forelse ($conversations as $conv)
                    @php
                        $isActive = $activeConversation && $activeConversation->id === $conv->id;
                        $firstLetter = strtoupper(substr($conv->contact_name ?: $conv->phone, 0, 1));
                        $colors = ['#0A1D37', '#107548', '#854d0e', '#1e40af', '#6b21a8'];
                        $avatarColor = $colors[abs(crc32($conv->phone)) % count($colors)];
                    @endphp
                    <a href="{{ route('admin.tfc.whatsapp.inbox', ['conversation_id' => $conv->id, 'filter' => $filter, 'search' => $search]) }}"
                       class="wa-convo-item {{ $isActive ? 'active' : '' }}">
                        <div class="wa-avatar" style="background: {{ $avatarColor }};">
                            {{ $firstLetter }}
                        </div>
                        <div class="overflow-hidden flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-truncate text-dark" style="font-size: 0.88rem;">
                                    {{ $conv->contact_name ?: '+' . $conv->phone }}
                                </span>
                                <span class="text-muted" style="font-size: 0.7rem;">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans(null, true) : '' }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted text-truncate pe-2" style="font-size: 0.76rem;">
                                    {{ $conv->last_message_preview ?: 'No messages yet' }}
                                </span>
                                @if ($conv->unread_count > 0)
                                    <span class="badge rounded-pill bg-success text-white" style="font-size: 0.65rem;">
                                        {{ $conv->unread_count }}
                                    </span>
                                @endif
                            </div>
                            @if ($conv->tfc_inquiry_id || $conv->tfc_grievance_id)
                                <div class="mt-1 d-flex gap-1">
                                    @if ($conv->tfc_inquiry_id)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.62rem;">Inquiry</span>
                                    @endif
                                    @if ($conv->tfc_grievance_id)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.62rem;">Grievance</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="text-center py-5 text-muted small">
                        <i class="bi bi-chat-left-dots fs-2 text-muted mb-2 d-block"></i>
                        No conversations found.
                    </div>
                @endforelse
            </div>
        </aside>

        {{-- Column 2: Main Interactive Chat Canvas --}}
        @if ($activeConversation)
            <main class="wa-chat-card" id="waChatColumn">
                {{-- Chat Header --}}
                <header class="wa-chat-header">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        {{-- Mobile Back button --}}
                        <a href="{{ route('admin.tfc.whatsapp.inbox', ['filter' => $filter]) }}" class="btn btn-link text-dark p-0 d-md-none me-1">
                            <i class="bi bi-arrow-left fs-5"></i>
                        </a>

                        <div class="wa-avatar" style="width: 38px; height: 38px; font-size: 0.95rem; background: #0A1D37;">
                            {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
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
                    @forelse ($activeConversation->messages as $msg)
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
                                @if ($msg->media_url)
                                    <div class="mb-2">
                                        @if (in_array($msg->message_type, ['image']))
                                            <a href="{{ $msg->media_url }}" target="_blank">
                                                <img src="{{ $msg->media_url }}" class="img-fluid rounded border" style="max-height: 220px;" alt="Image">
                                            </a>
                                        @elseif (in_array($msg->message_type, ['audio', 'voice']))
                                            <audio controls class="w-100 mt-1" style="max-height: 36px;">
                                                <source src="{{ $msg->media_url }}">
                                            </audio>
                                        @else
                                            <a href="{{ $msg->media_url }}" target="_blank" class="btn btn-sm btn-light border">
                                                <i class="bi bi-paperclip me-1"></i>View Attachment
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                {{-- Text / Template Body --}}
                                <div style="white-space: pre-wrap;">{{ $msg->body }}</div>

                                {{-- Meta Timestamp & Delivery Checkmarks --}}
                                <div class="d-flex align-items-center justify-content-end gap-1 mt-1 text-muted" style="font-size: 0.65rem;">
                                    <span>{{ $msg->created_at->format('h:i A') }}</span>
                                    @if ($isOut)
                                        @if ($msg->status === 'read')
                                            <i class="bi bi-check2-all text-primary" title="Read"></i>
                                        @elseif ($msg->status === 'delivered')
                                            <i class="bi bi-check2-all text-muted" title="Delivered"></i>
                                        @elseif ($msg->status === 'failed')
                                            <i class="bi bi-exclamation-circle-fill text-danger" title="Delivery Failed"></i>
                                        @else
                                            <i class="bi bi-check2 text-muted" title="Sent"></i>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small my-auto">
                            <i class="bi bi-chat-quote fs-1 text-muted opacity-50 mb-2 d-block"></i>
                            No messages in this chat yet.<br>Start the conversation below or send a template.
                        </div>
                    @endforelse
                </div>

                {{-- Composer Input Dock --}}
                <div class="wa-composer-wrapper">
                    <form id="waSendForm" onsubmit="event.preventDefault(); sendChatMessage();">
                        @csrf
                        <div class="d-flex align-items-center gap-2">
                            {{-- Quick replies button --}}
                            <div class="dropdown">
                                <button type="button" class="wa-composer-icon" data-bs-toggle="dropdown" title="Quick Replies (Type / in chat)">
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

                            {{-- Input capsule --}}
                            <div class="wa-input-capsule">
                                <textarea id="waMessageInput" rows="1" placeholder="Type a message or '/' for quick replies..." onkeydown="handleComposerKey(event)"></textarea>
                            </div>

                            {{-- Send button --}}
                            <button type="submit" class="wa-send-btn" id="waSendBtn" title="Send Message">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </main>

            {{-- Column 3: Direct Seller & Customer 360 Context Drawer --}}
            <aside class="wa-context-card d-none d-lg-flex" id="waDetailColumn">
                {{-- Header & Profile Summary --}}
                <div class="p-3 border-bottom text-center bg-light">
                    <div class="wa-avatar mx-auto mb-2" style="width: 54px; height: 54px; font-size: 1.3rem; background: #0A1D37;">
                        {{ strtoupper(substr($activeConversation->contact_name ?: $activeConversation->phone, 0, 1)) }}
                    </div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $activeConversation->contact_name ?: 'Prospective Distributor' }}</h6>
                    <div class="text-muted small">+{{ $activeConversation->phone }}</div>
                    <div class="mt-2">
                        <a href="tel:+{{ $activeConversation->phone }}" class="btn btn-sm btn-outline-success py-1 px-3 rounded-pill" style="font-size: 0.75rem;">
                            <i class="bi bi-telephone-outbound me-1"></i>Direct Call
                        </a>
                    </div>
                </div>

                {{-- Linked Direct-Selling Inquiry / Application --}}
                @if ($activeConversation->tfcInquiry)
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-primary"><i class="bi bi-person-badge me-1"></i>Seller Application</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">
                                {{ ucfirst($activeConversation->tfcInquiry->status) }}
                            </span>
                        </div>
                        <div class="small text-muted mb-1">
                            <strong>Type:</strong> {{ $activeConversation->tfcInquiry->type === 'seller_application' ? 'Distributor Application' : 'General' }}
                        </div>
                        @if ($activeConversation->tfcInquiry->city || $activeConversation->tfcInquiry->state)
                            <div class="small text-muted mb-1">
                                <strong>Location:</strong> {{ $activeConversation->tfcInquiry->city }}, {{ $activeConversation->tfcInquiry->state }}
                            </div>
                        @endif
                        <a href="{{ route('admin.tfc.inquiries.show', $activeConversation->tfcInquiry) }}" class="btn btn-xs btn-outline-primary w-100 mt-2" style="font-size: 0.72rem;">
                            View Full Application
                        </a>
                    </div>
                @endif

                {{-- Linked Grievance Ticket --}}
                @if ($activeConversation->tfcGrievance)
                    <div class="p-3 border-bottom">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-danger"><i class="bi bi-shield-exclamation me-1"></i>Grievance Ticket</span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">
                                #{{ $activeConversation->tfcGrievance->ticket_number }}
                            </span>
                        </div>
                        <div class="small text-muted mb-1">
                            <strong>Category:</strong> {{ ucwords(str_replace('_', ' ', $activeConversation->tfcGrievance->category)) }}
                        </div>
                        <div class="small text-muted mb-1">
                            <strong>Status:</strong> {{ ucfirst($activeConversation->tfcGrievance->status) }}
                        </div>
                        <a href="{{ route('admin.tfc.grievances.show', $activeConversation->tfcGrievance) }}" class="btn btn-xs btn-outline-danger w-100 mt-2" style="font-size: 0.72rem;">
                            View Ticket Resolution
                        </a>
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
                </div>
            </aside>
        @else
            <main class="wa-chat-card d-flex align-items-center justify-content-center text-center p-5">
                <div>
                    <div class="wa-avatar mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem; background: #25D366;">
                        <i class="bi bi-whatsapp"></i>
                    </div>
                    <h5 class="fw-bold text-dark">TFC WhatsApp Direct-Selling CRM</h5>
                    <p class="text-muted small" style="max-width: 380px;">
                        Select a conversation from the directory on the left to start live chatting, answer candidate questions, or resolve consumer grievances.
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
                <h6 class="modal-title fw-bold"><i class="bi bi-paperclip text-success me-2"></i>Send Image or Document</h6>
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
@endif

@push('scripts')
<script>
const activeConvId = {{ $activeConversation ? $activeConversation->id : 'null' }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Auto-scroll stream to bottom
function scrollToBottom() {
    const stream = document.getElementById('waMessageStream');
    if (stream) {
        stream.scrollTop = stream.scrollHeight;
    }
}
window.addEventListener('DOMContentLoaded', scrollToBottom);

// Handle Enter to send
function handleComposerKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
}

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

    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/send`, {
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

    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/send-template`, {
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

    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/send-media`, {
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
    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/assign`, {
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
    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/status`, {
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

    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/note`, {
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

    fetch(`{{ url('admin/tfc/whatsapp/inbox') }}/${activeConvId}/poll?after_id=${afterId}`)
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
