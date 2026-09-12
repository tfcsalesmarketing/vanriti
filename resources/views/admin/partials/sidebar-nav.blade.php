<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
    </li>

    <li class="nav-heading">Catalogue</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
           href="{{ route('admin.products.index') }}">
            <i class="bi bi-box-seam"></i><span>Products</span>
        </a>
    </li>
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
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
           href="{{ route('admin.inventory.index') }}">
            <i class="bi bi-box2"></i><span>Inventory</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dadi.product-profiles.*') ? 'active' : '' }}"
           href="{{ route('admin.dadi.product-profiles.index') }}">
            <i class="bi bi-robot"></i><span>Dadi Product Profiles</span>
        </a>
    </li>

    <li class="nav-heading">Orders</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
           href="{{ route('admin.orders.index') }}">
            <i class="bi bi-cart-check"></i><span>Orders</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}"
           href="{{ route('admin.refunds.index') }}">
            <i class="bi bi-arrow-counterclockwise"></i><span>Refunds</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}"
           href="{{ route('admin.returns.index') }}">
            <i class="bi bi-return"></i><span>Returns</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}"
           href="{{ route('admin.shipments.index') }}">
            <i class="bi bi-truck"></i><span>Shipments</span>
        </a>
    </li>

    <li class="nav-heading">Customers</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
           href="{{ route('admin.customers.index') }}">
            <i class="bi bi-people"></i><span>Customers</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"
           href="{{ route('admin.coupons.index') }}">
            <i class="bi bi-ticket-perforated"></i><span>Coupons</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
           href="{{ route('admin.reviews.index') }}">
            <i class="bi bi-star"></i><span>Reviews</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.newsletters.*') ? 'active' : '' }}"
           href="{{ route('admin.newsletters.index') }}">
            <i class="bi bi-envelope-heart"></i><span>Newsletter</span>
        </a>
    </li>

    <li class="nav-heading">Content</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.media.*') ? 'active' : '' }}"
           href="{{ route('admin.media.index') }}">
            <i class="bi bi-folder2-open"></i><span>Media Library</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"
           href="{{ route('admin.banners.index') }}">
            <i class="bi bi-images"></i><span>Banners</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.blogs.*') ? 'active' : '' }}"
           href="{{ route('admin.blogs.index') }}">
            <i class="bi bi-journal-text"></i><span>Blogs</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
           href="{{ route('admin.pages.index') }}">
            <i class="bi bi-file-text"></i><span>Pages</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}"
           href="{{ route('admin.faqs.index') }}">
            <i class="bi bi-question-circle"></i><span>FAQs</span>
        </a>
    </li>

    <li class="nav-heading">System</li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}"
           href="{{ route('admin.admins.index') }}">
            <i class="bi bi-person-gear"></i><span>Admins</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
           href="{{ route('admin.roles.index') }}">
            <i class="bi bi-shield-lock"></i><span>Roles & Permissions</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}"
           href="{{ route('admin.activities.index') }}">
            <i class="bi bi-clock-history"></i><span>Activity Log</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
           href="{{ route('admin.settings.index') }}">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>
    </li>
</ul>