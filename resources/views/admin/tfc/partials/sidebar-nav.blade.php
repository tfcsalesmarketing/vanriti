@php
    $admin = auth('admin')->user();
    $newInquiriesCount = \App\Models\Tfc\TfcInquiry::where('status', 'new')->count();
    $pendingGrievanceCount = \App\Models\Tfc\TfcGrievanceTicket::where('status', '!=', 'resolved')->count();
@endphp

<div class="px-3 py-2 mb-2 rounded d-flex align-items-center gap-2" style="background: rgba(212, 155, 36, 0.15); border: 1px solid rgba(212, 155, 36, 0.3);">
    <i class="bi bi-building text-warning fs-5"></i>
    <div>
        <div class="fw-bold small text-dark" style="letter-spacing: 0.5px;">TFC SALES & MKTG</div>
        <div class="text-muted" style="font-size: 0.7rem;">Direct Selling Management</div>
    </div>
</div>

<ul class="nav flex-column">
    {{-- Back to VANRITI Store Admin --}}
    <li class="nav-item mb-2">
        <a class="nav-link text-success fw-semibold" href="{{ route('admin.dashboard') }}" style="background: rgba(25, 135, 84, 0.08); border-radius: 6px;">
            <i class="bi bi-arrow-left-circle text-success"></i><span>Back to VANRITI</span>
        </a>
    </li>

    {{-- TFC Dashboard --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.dashboard') ? 'active' : '' }}" href="{{ route('admin.tfc.dashboard') }}">
            <i class="bi bi-speedometer2"></i><span>TFC Dashboard</span>
        </a>
    </li>

    {{-- Live Synced Catalogue --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.products.*') ? 'active' : '' }}" href="{{ route('admin.tfc.products.index') }}">
            <i class="bi bi-box-seam"></i><span>Synced Products</span>
        </a>
    </li>

    {{-- CMS Pages & Statutory Documents --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.pages.*') ? 'active' : '' }}" href="{{ route('admin.tfc.pages.index') }}">
            <i class="bi bi-file-earmark-richtext"></i><span>Pages &amp; Statutory</span>
        </a>
    </li>

    {{-- Corporate Blogs & Updates --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.blogs.*') ? 'active' : '' }}" href="{{ route('admin.tfc.blogs.index') }}">
            <i class="bi bi-newspaper"></i><span>Blogs &amp; News</span>
        </a>
    </li>

    {{-- Banners & CTAs --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.banners.*') ? 'active' : '' }}" href="{{ route('admin.tfc.banners.index') }}">
            <i class="bi bi-images"></i><span>Hero &amp; Banners</span>
        </a>
    </li>

    {{-- Navigation Menus --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.menus.*') ? 'active' : '' }}" href="{{ route('admin.tfc.menus.index') }}">
            <i class="bi bi-menu-button-wide"></i><span>Navigation Menus</span>
        </a>
    </li>

    {{-- Inquiries & Prospective Direct-Seller Applications --}}
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.inquiries.*') ? 'active' : '' }}" href="{{ route('admin.tfc.inquiries.index') }}">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-people"></i><span>Inquiries &amp; Apps</span></span>
            @if ($newInquiriesCount > 0)
                <span class="badge bg-primary rounded-pill">{{ $newInquiriesCount }}</span>
            @endif
        </a>
    </li>

    {{-- Statutory Consumer Grievances (Rule 7 Direct Selling Rules) --}}
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.grievances.*') ? 'active' : '' }}" href="{{ route('admin.tfc.grievances.index') }}">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-shield-exclamation text-danger"></i><span>Grievances (Rule 7)</span></span>
            @if ($pendingGrievanceCount > 0)
                <span class="badge bg-danger rounded-pill">{{ $pendingGrievanceCount }}</span>
            @endif
        </a>
    </li>

    {{-- WhatsApp Direct Selling Suite --}}
    @php
        $unreadTfcWaCount = \App\Models\WhatsAppConversation::where('channel', 'tfc')->where('unread_count', '>', 0)->count();
    @endphp
    <li class="nav-heading mt-3 text-uppercase text-muted fw-bold" style="font-size: 0.7rem; padding: 0.5rem 1rem 0.25rem;">
        WhatsApp CMS &amp; CRM
    </li>

    {{-- Live Chat Inbox --}}
    <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.whatsapp.inbox*') ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.inbox') }}">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-whatsapp text-success"></i><span>WhatsApp Inbox</span></span>
            @if ($unreadTfcWaCount > 0)
                <span class="badge bg-success rounded-pill">{{ $unreadTfcWaCount }}</span>
            @endif
        </a>
    </li>

    {{-- Broadcast Campaigns --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.campaigns.*') ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.campaigns.index') }}">
            <i class="bi bi-broadcast"></i><span>Seller Campaigns</span>
        </a>
    </li>

    {{-- Chatbot & Quick Replies --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.bot.*') ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.bot.index') }}">
            <i class="bi bi-robot"></i><span>Chatbot &amp; Replies</span>
        </a>
    </li>

    {{-- Meta Templates --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.templates.*') ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.templates.index') }}">
            <i class="bi bi-file-earmark-code"></i><span>Meta Templates</span>
        </a>
    </li>

    {{-- WhatsApp Cloud API & Profile --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.profile*') ? 'active' : '' }}" href="{{ route('admin.tfc.whatsapp.profile') }}">
            <i class="bi bi-gear-wide-connected"></i><span>WhatsApp API &amp; Widget</span>
        </a>
    </li>

    {{-- TFC Settings & Statutory Disclosures --}}
    <li class="nav-heading mt-3 text-uppercase text-muted fw-bold" style="font-size: 0.7rem; padding: 0.5rem 1rem 0.25rem;">
        Administration
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.settings.*') ? 'active' : '' }}" href="{{ route('admin.tfc.settings.index') }}">
            <i class="bi bi-sliders"></i><span>TFC Settings</span>
        </a>
    </li>
</ul>
