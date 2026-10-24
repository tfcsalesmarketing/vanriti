@php
    $admin = auth('admin')->user();
    $_waUnreadCount = \App\Models\WhatsAppConversation::sum('unread_count');

    $catActive = request()->routeIs('admin.products.*', 'admin.categories.*', 'admin.main-categories.*', 'admin.inventory.*', 'admin.dadi.product-profiles.*');
    $ordersActive = request()->routeIs('admin.orders.*', 'admin.shipments.*', 'admin.returns.*', 'admin.refunds.*');
    $waActive = request()->routeIs('admin.whatsapp.*');
    $custActive = request()->routeIs('admin.customers.*', 'admin.carts.*', 'admin.wishlists.*', 'admin.coupons.*', 'admin.reviews.*', 'admin.newsletters.*', 'admin.contact-messages.*');
    $contentActive = request()->routeIs('admin.media.*', 'admin.banners.*', 'admin.blogs.*', 'admin.pages.*', 'admin.faqs.*');
    $sysActive = request()->routeIs('admin.admins.*', 'admin.roles.*', 'admin.activities.*', 'admin.settings.*');
@endphp

<ul class="nav flex-column">
    {{-- Dashboard --}}
    @if ($admin->hasPermission('view-dashboard'))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
    </li>
    @endif

    {{-- Analytics Center --}}
    @if ($admin->hasPermission('view-reports'))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.analytics') ? 'active' : '' }}"
           href="{{ route('admin.analytics') }}">
            <i class="bi bi-graph-up"></i><span>Analytics Center</span>
        </a>
    </li>
    @endif

    {{-- Catalogue & Inventory Dropdown --}}
    @if ($admin->hasPermission(['view-products', 'manage-products', 'view-categories', 'manage-categories', 'view-inventory', 'manage-inventory', 'review-dadi-product-profiles']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $catActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseCatalogue"
           role="button"
           aria-expanded="{{ $catActive ? 'true' : 'false' }}"
           aria-controls="collapseCatalogue">
            <i class="bi bi-box-seam"></i>
            <span>Catalogue &amp; Stock</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $catActive ? 'show' : '' }}" id="collapseCatalogue">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission(['view-products', 'manage-products']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                       href="{{ route('admin.products.index') }}">
                        <i class="bi bi-box-seam"></i><span>Products</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-categories', 'manage-categories']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                       href="{{ route('admin.categories.index') }}">
                        <i class="bi bi-diagram-3"></i><span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.main-categories.*') ? 'active' : '' }}"
                       href="{{ route('admin.main-categories.index') }}">
                        <i class="bi bi-folder"></i><span>Main Categories</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-inventory', 'manage-inventory']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
                       href="{{ route('admin.inventory.index') }}">
                        <i class="bi bi-box2"></i><span>Inventory</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('review-dadi-product-profiles'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dadi.product-profiles.*') ? 'active' : '' }}"
                       href="{{ route('admin.dadi.product-profiles.index') }}">
                        <i class="bi bi-robot"></i><span>Dadi AI Profiles</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    {{-- Orders & Logistics Dropdown --}}
    @if ($admin->hasPermission(['view-orders', 'manage-orders', 'view-shipments', 'manage-shipments', 'view-returns', 'manage-returns', 'view-refunds', 'manage-refunds']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $ordersActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseOrders"
           role="button"
           aria-expanded="{{ $ordersActive ? 'true' : 'false' }}"
           aria-controls="collapseOrders">
            <i class="bi bi-cart-check"></i>
            <span>Orders &amp; Shipping</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $ordersActive ? 'show' : '' }}" id="collapseOrders">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission(['view-orders', 'manage-orders']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                       href="{{ route('admin.orders.index') }}">
                        <i class="bi bi-receipt"></i><span>All Orders</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-shipments', 'manage-shipments']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}"
                       href="{{ route('admin.shipments.index') }}">
                        <i class="bi bi-truck"></i><span>Shipments (ShipMojo)</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-returns', 'manage-returns']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}"
                       href="{{ route('admin.returns.index') }}">
                        <i class="bi bi-arrow-return-left"></i><span>Returns</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-refunds', 'manage-refunds']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}"
                       href="{{ route('admin.refunds.index') }}">
                        <i class="bi bi-arrow-counterclockwise"></i><span>Refunds</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    {{-- WhatsApp CRM & Suite Dropdown --}}
    @if ($admin->hasPermission(['view-whatsapp-inbox', 'manage-whatsapp-inbox', 'view-whatsapp-campaigns', 'manage-whatsapp-campaigns', 'manage-whatsapp-bot', 'manage-whatsapp-templates', 'manage-whatsapp-profile']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $waActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseWhatsApp"
           role="button"
           aria-expanded="{{ $waActive ? 'true' : 'false' }}"
           aria-controls="collapseWhatsApp">
            <i class="bi bi-whatsapp" style="color: #25D366;"></i>
            <span>WhatsApp CRM</span>
            @if ($_waUnreadCount > 0)
                <span class="badge rounded-pill bg-success ms-auto me-1" style="font-size: 0.68rem; padding: 0.25em 0.55em;">{{ $_waUnreadCount }}</span>
            @endif
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $waActive ? 'show' : '' }}" id="collapseWhatsApp">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission(['view-whatsapp-inbox', 'manage-whatsapp-inbox']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.whatsapp.inbox*') ? 'active' : '' }}"
                       href="{{ route('admin.whatsapp.inbox') }}">
                        <i class="bi bi-chat-dots-fill" style="color: #25D366;"></i>
                        <span>Live Chat Inbox</span>
                        @if ($_waUnreadCount > 0)
                            <span class="badge rounded-pill bg-success ms-auto" style="font-size: 0.65rem;">{{ $_waUnreadCount }}</span>
                        @endif
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-whatsapp-campaigns', 'manage-whatsapp-campaigns']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.whatsapp.campaigns*') ? 'active' : '' }}"
                       href="{{ route('admin.whatsapp.campaigns.index') }}">
                        <i class="bi bi-megaphone"></i>
                        <span>Broadcasts</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-whatsapp-bot'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.whatsapp.bot*') ? 'active' : '' }}"
                       href="{{ route('admin.whatsapp.bot.index') }}">
                        <i class="bi bi-robot"></i>
                        <span>Chatbot &amp; Rules</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-whatsapp-templates'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.whatsapp.templates*') ? 'active' : '' }}"
                       href="{{ route('admin.whatsapp.templates.index') }}">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Meta Templates</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-whatsapp-profile'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.whatsapp.profile*') ? 'active' : '' }}"
                       href="{{ route('admin.whatsapp.profile') }}">
                        <i class="bi bi-person-badge"></i>
                        <span>Profile &amp; Health</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    {{-- Customers & Growth Dropdown --}}
    @if ($admin->hasPermission(['view-customers', 'manage-customers', 'view-coupons', 'manage-coupons', 'view-reviews', 'manage-reviews', 'manage-newsletters']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $custActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseCustomers"
           role="button"
           aria-expanded="{{ $custActive ? 'true' : 'false' }}"
           aria-controls="collapseCustomers">
            <i class="bi bi-people"></i>
            <span>Customers &amp; Growth</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $custActive ? 'show' : '' }}" id="collapseCustomers">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission(['view-customers', 'manage-customers']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
                       href="{{ route('admin.customers.index') }}">
                        <i class="bi bi-person-lines-fill"></i><span>Customer Directory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.carts.*') ? 'active' : '' }}"
                       href="{{ route('admin.carts.index') }}">
                        <i class="bi bi-cart-x"></i><span>Abandoned Carts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.wishlists.*') ? 'active' : '' }}"
                       href="{{ route('admin.wishlists.index') }}">
                        <i class="bi bi-heart"></i><span>Wishlist Items</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-coupons', 'manage-coupons']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"
                       href="{{ route('admin.coupons.index') }}">
                        <i class="bi bi-ticket-perforated"></i><span>Discount Coupons</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-reviews', 'manage-reviews']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
                       href="{{ route('admin.reviews.index') }}">
                        <i class="bi bi-star"></i><span>Product Reviews</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-newsletters'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.newsletters.*') ? 'active' : '' }}"
                       href="{{ route('admin.newsletters.index') }}">
                        <i class="bi bi-envelope-heart"></i><span>Newsletter Leads</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}"
                       href="{{ route('admin.contact-messages.index') }}">
                        <i class="bi bi-inbox"></i><span>Contact Inquiries</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    {{-- Content & Storefront Dropdown --}}
    @if ($admin->hasPermission(['view-media', 'manage-media', 'view-banners', 'manage-banners', 'view-blogs', 'manage-blogs', 'view-pages', 'manage-pages', 'view-faqs', 'manage-faqs']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $contentActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseContent"
           role="button"
           aria-expanded="{{ $contentActive ? 'true' : 'false' }}"
           aria-controls="collapseContent">
            <i class="bi bi-window-stack"></i>
            <span>Storefront &amp; CMS</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $contentActive ? 'show' : '' }}" id="collapseContent">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission(['view-media', 'manage-media']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}"
                       href="{{ route('admin.media.index') }}">
                        <i class="bi bi-folder2-open"></i><span>Media Library</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-banners', 'manage-banners']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"
                       href="{{ route('admin.banners.index') }}">
                        <i class="bi bi-images"></i><span>Banners &amp; Sliders</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-blogs', 'manage-blogs']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}"
                       href="{{ route('admin.blogs.index') }}">
                        <i class="bi bi-journal-text"></i><span>Blog Articles</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-pages', 'manage-pages']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
                       href="{{ route('admin.pages.index') }}">
                        <i class="bi bi-file-text"></i><span>Custom Pages</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-faqs', 'manage-faqs']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}"
                       href="{{ route('admin.faqs.index') }}">
                        <i class="bi bi-question-circle"></i><span>FAQs</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    {{-- System & Settings Dropdown --}}
    @if ($admin->hasPermission(['manage-admins', 'manage-roles', 'view-activities', 'manage-activities', 'manage-settings']))
    <li class="nav-item">
        <a class="nav-link sidebar-group-toggle {{ $sysActive ? 'active-parent' : '' }}"
           data-bs-toggle="collapse"
           href="#collapseSystem"
           role="button"
           aria-expanded="{{ $sysActive ? 'true' : 'false' }}"
           aria-controls="collapseSystem">
            <i class="bi bi-gear"></i>
            <span>System Controls</span>
            <i class="bi bi-chevron-right chevron-icon"></i>
        </a>
        <div class="collapse {{ $sysActive ? 'show' : '' }}" id="collapseSystem">
            <ul class="sidebar-submenu">
                @if ($admin->hasPermission('manage-settings'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                       href="{{ route('admin.settings.index') }}">
                        <i class="bi bi-sliders"></i><span>Store Settings</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-admins'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}"
                       href="{{ route('admin.admins.index') }}">
                        <i class="bi bi-person-gear"></i><span>Admin Accounts</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission('manage-roles'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                       href="{{ route('admin.roles.index') }}">
                        <i class="bi bi-shield-lock"></i><span>Roles &amp; Permissions</span>
                    </a>
                </li>
                @endif
                @if ($admin->hasPermission(['view-activities', 'manage-activities']))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}"
                       href="{{ route('admin.activities.index') }}">
                        <i class="bi bi-clock-history"></i><span>Activity Audit Log</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif
</ul>