<ul class="list-group shadow-sm d-lg-block d-flex flex-wrap" style="border-radius: 14px; overflow: hidden;">
    <li class="list-group-item p-0">
        <a href="{{ route('account.dashboard') }}" class="d-block px-3 py-3 small fw-semibold {{ request()->routeIs('account.dashboard') ? 'bg-light' : '' }}">
            <i class="bi bi-grid me-2"></i>Dashboard
        </a>
    </li>
    <li class="list-group-item p-0">
        <a href="{{ route('account.orders') }}" class="d-block px-3 py-3 small fw-semibold {{ request()->routeIs('account.orders', 'account.order') ? 'bg-light' : '' }}">
            <i class="bi bi-bag-check me-2"></i>My Orders
        </a>
    </li>
    <li class="list-group-item p-0">
        <a href="{{ route('account.addresses') }}" class="d-block px-3 py-3 small fw-semibold {{ request()->routeIs('account.addresses*') ? 'bg-light' : '' }}">
            <i class="bi bi-geo-alt me-2"></i>Addresses
        </a>
    </li>
    <li class="list-group-item p-0">
        <a href="{{ route('wishlist.index') }}" class="d-block px-3 py-3 small fw-semibold">
            <i class="bi bi-heart me-2"></i>Wishlist
        </a>
    </li>
    <li class="list-group-item p-0">
        <a href="{{ route('track') }}" class="d-block px-3 py-3 small fw-semibold">
            <i class="bi bi-truck me-2"></i>Track Order
        </a>
    </li>
    <li class="list-group-item p-0">
        <form method="POST" action="{{ route('logout') }}" class="d-block">
            @csrf
            <button type="submit" class="vr-nav-logout-btn d-block w-100 text-start px-3 py-3 small fw-semibold">
                <i class="bi bi-box-arrow-right me-2"></i>Log Out
            </button>
        </form>
    </li>
</ul>