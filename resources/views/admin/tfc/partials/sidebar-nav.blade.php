@php
    $admin = auth('admin')->user();
    $newInquiriesCount = \App\Models\Tfc\TfcInquiry::where('status', 'new')->count();
    $pendingGrievanceCount = \App\Models\Tfc\TfcGrievanceTicket::where('status', '!=', 'resolved')->count();
    $unreadTfcWaCount = \App\Models\WhatsAppConversation::where('channel', 'tfc')->where('unread_count', '>', 0)->count();

    // Group active checks matching VANRITI accordion pattern
    $crmActive = request()->routeIs('admin.tfc.inquiries.*', 'admin.tfc.grievances.*');
    $waActive = request()->routeIs('admin.tfc.whatsapp.*');
    $cmsActive = request()->routeIs('admin.tfc.pages.*', 'admin.tfc.blogs.*', 'admin.tfc.banners.*', 'admin.tfc.menus.*', 'admin.tfc.products.*');
    $settingsActive = request()->routeIs('admin.tfc.settings.*');
@endphp

{{-- Switch back to VANRITI Store --}}
<div class="px-2 mb-2">
    <a class="nav-link text-success fw-semibold d-flex align-items-center gap-2 p-2 rounded" 
       href="{{ route('admin.dashboard') }}" 
       style="background: rgba(25, 135, 84, 0.08); font-size: 0.85rem; transition: background 0.2s ease;">
        <i class="bi bi-arrow-left-circle fs-6"></i>
        <span>Back to VANRITI</span>
    </a>
</div>

{{-- TFC Corporate Direct-Selling Brand Header --}}
<div class="px-3 py-2 mb-3 rounded-3 d-flex align-items-center gap-2" 
     style="background: #0A1D37; color: #FFFFFF; border: 1px solid rgba(212, 155, 36, 0.4); box-shadow: 0 2px 6px rgba(10, 29, 55, 0.15);">
    <i class="bi bi-building fs-5" style="color: #D49B24;"></i>
    <div class="overflow-hidden">
        <div class="fw-bold small text-truncate" style="letter-spacing: 0.5px; color: #F8FAFC;">TFC SALES &amp; MKTG</div>
        <div class="small text-truncate" style="font-size: 0.68rem; color: #CBD5E1;">Direct-Selling Suite</div>
    </div>
</div>

<ul class="nav flex-column">
    {{-- 1. Dashboard --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.tfc.dashboard') ? 'active' : '' }}" 
           href="{{ route('admin.tfc.dashboard') }}">
            <i class="bi bi-grid-1x2"></i>
            <span>TFC Dashboard</span>
        </a>
    </li>

    {{-- 2. Direct-Selling Desk & CRM --}}
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $crmActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseTfcCrm"
           role="button"
           aria-expanded="{{ $crmActive ? 'true' : 'false' }}"
           aria-controls="collapseTfcCrm">
            <i class="bi bi-people"></i>
            <span>Direct Selling Desk</span>
            @if ($newInquiriesCount + $pendingGrievanceCount > 0)
                <span class="badge rounded-pill bg-danger ms-auto me-1" style="font-size: 0.68rem; padding: 0.25em 0.55em;">
                    {{ $newInquiriesCount + $pendingGrievanceCount }}
                </span>
            @endif
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $crmActive ? 'show' : '' }}" id="collapseTfcCrm">
            <ul class="sidebar-submenu">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.inquiries.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.inquiries.index') }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-lines-fill"></i>
                            <span>Inquiries &amp; Apps</span>
                        </span>
                        @if ($newInquiriesCount > 0)
                            <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;">{{ $newInquiriesCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.grievances.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.grievances.index') }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-exclamation text-danger"></i>
                            <span>Grievances (Rule 7)</span>
                        </span>
                        @if ($pendingGrievanceCount > 0)
                            <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">{{ $pendingGrievanceCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </div>
    </li>

    {{-- 3. WhatsApp CRM (Collapsible, matching VANRITI WhatsApp dropdown) --}}
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $waActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseTfcWhatsApp"
           role="button"
           aria-expanded="{{ $waActive ? 'true' : 'false' }}"
           aria-controls="collapseTfcWhatsApp">
            <i class="bi bi-whatsapp" style="color: #25D366;"></i>
            <span>WhatsApp CRM</span>
            @if ($unreadTfcWaCount > 0)
                <span class="badge rounded-pill bg-success ms-auto me-1" style="font-size: 0.68rem; padding: 0.25em 0.55em;">
                    {{ $unreadTfcWaCount }}
                </span>
            @endif
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $waActive ? 'show' : '' }}" id="collapseTfcWhatsApp">
            <ul class="sidebar-submenu">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('admin.tfc.whatsapp.inbox*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.whatsapp.inbox') }}">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-chat-dots-fill" style="color: #25D366;"></i>
                            <span>Live Chat Inbox</span>
                        </span>
                        @if ($unreadTfcWaCount > 0)
                            <span class="badge bg-success rounded-pill" style="font-size: 0.65rem;">{{ $unreadTfcWaCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.campaigns.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.whatsapp.campaigns.index') }}">
                        <i class="bi bi-megaphone"></i>
                        <span>Broadcast Campaigns</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.bot.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.whatsapp.bot.index') }}">
                        <i class="bi bi-robot"></i>
                        <span>Chatbot &amp; Quick Replies</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.templates.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.whatsapp.templates.index') }}">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Meta Templates</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.whatsapp.profile*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.whatsapp.profile') }}">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span>API &amp; Floating Widget</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>

    {{-- 4. Content & Website CMS --}}
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $cmsActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseTfcCms"
           role="button"
           aria-expanded="{{ $cmsActive ? 'true' : 'false' }}"
           aria-controls="collapseTfcCms">
            <i class="bi bi-window-stack"></i>
            <span>Content &amp; Website</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $cmsActive ? 'show' : '' }}" id="collapseTfcCms">
            <ul class="sidebar-submenu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.products.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.products.index') }}">
                        <i class="bi bi-box-seam"></i>
                        <span>Live Synced Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.pages.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.pages.index') }}">
                        <i class="bi bi-file-earmark-richtext"></i>
                        <span>Pages &amp; Statutory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.blogs.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.blogs.index') }}">
                        <i class="bi bi-newspaper"></i>
                        <span>Blogs &amp; News</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.banners.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.banners.index') }}">
                        <i class="bi bi-images"></i>
                        <span>Hero &amp; Sliders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.tfc.menus.*') ? 'active' : '' }}" 
                       href="{{ route('admin.tfc.menus.index') }}">
                        <i class="bi bi-menu-button-wide"></i>
                        <span>Navigation Menus</span>
                    </a>
                </li>
            </ul>
        </div>
    </li>

    {{-- 5. Administration & Statutory Disclosures --}}
    <li class="nav-item">
        <a class="nav-link {{ $settingsActive ? 'active' : '' }}" 
           href="{{ route('admin.tfc.settings.index') }}">
            <i class="bi bi-shield-check" style="color: #D49B24;"></i>
            <span>Statutory Disclosures</span>
        </a>
    </li>
</ul>
