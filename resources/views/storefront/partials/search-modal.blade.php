{{-- Modern Predictive Search Modal --}}
<div class="modal fade vr-search-modal" id="vrSearchModal" tabindex="-1" aria-labelledby="vrSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable vr-search-dialog">
        <div class="modal-content border-0 shadow-lg vr-search-modal-content">
            
            {{-- Fixed Modal Header --}}
            <div class="modal-header border-0 pb-1 pt-3 px-3 px-sm-4 d-flex align-items-center justify-content-between flex-shrink-0">
                <span class="small text-uppercase fw-bold text-muted" id="vrSearchModalLabel" style="letter-spacing: 0.5px; font-size: 11px;">Search Vanriti Herbal &amp; Ayurvedic Care</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Sticky Search Input Bar --}}
            <div class="px-3 px-sm-4 pb-2 pt-1 flex-shrink-0 bg-white">
                <form action="{{ route('shop.index') }}" method="GET" id="vrLiveSearchForm" class="mb-0">
                    <div class="input-group input-group-lg border rounded-3 p-1 shadow-xs vr-search-input-wrap">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-2 ps-sm-3 pe-2">
                            <i class="bi bi-search fs-5 text-success"></i>
                        </span>
                        <input type="search" name="q" id="vrSearchInput" class="form-control bg-transparent border-0 fs-6 ps-0"
                               placeholder="Search hair care, skin care, amla, beetroot, wellness..." autocomplete="off" autofocus>
                        <div class="d-none align-items-center pe-3" id="vrSearchSpinner">
                            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Scrollable Results Body --}}
            <div class="modal-body px-3 px-sm-4 py-2 vr-search-modal-body">
                
                {{-- Live Results Container --}}
                <div id="vrSearchResultsContainer" class="d-none">
                    {{-- Categories Match --}}
                    <div id="vrSearchCategoriesWrap" class="mb-3 d-none">
                        <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Categories</div>
                        <div class="d-flex flex-wrap gap-1.5" id="vrSearchCategoryPills"></div>
                    </div>

                    {{-- Products Match --}}
                    <div id="vrSearchProductsWrap" class="d-none">
                        <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.5px;">Products</div>
                        <div class="d-flex flex-column gap-1.5" id="vrSearchProductsList"></div>
                    </div>
                </div>

                {{-- Empty State / Initial Suggestions --}}
                <div id="vrSearchEmptyState" class="py-3 text-center text-muted">
                    <p class="small mb-2 text-secondary">Popular searches:</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('shop.index', ['q' => 'Hair Care']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Hair Care</a>
                        <a href="{{ route('shop.index', ['q' => 'Skin Care']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Skin Care</a>
                        <a href="{{ route('shop.index', ['q' => 'Health Care']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Health Care</a>
                        <a href="{{ route('shop.index', ['q' => 'Amla']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Amla</a>
                        <a href="{{ route('shop.index', ['q' => 'Beetroot']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Beetroot</a>
                        <a href="{{ route('shop.index', ['q' => 'Face Pack']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none px-3 py-2 border">Face Pack</a>
                    </div>
                </div>

                {{-- No Matches Found --}}
                <div id="vrSearchNoResults" class="py-4 text-center text-muted d-none">
                    <i class="bi bi-search fs-2 mb-2 d-block text-muted opacity-75"></i>
                    <h6 class="fw-bold mb-1 text-dark">No matching items found</h6>
                    <p class="small mb-0 text-secondary">Try checking your spelling or searching for a broader term.</p>
                </div>
            </div>

            {{-- Fixed / Bottom Sticky View All Results Footer Link --}}
            <div class="modal-footer border-top py-2.5 px-3 px-sm-4 bg-light d-none justify-content-center flex-shrink-0" id="vrSearchViewAllWrap">
                <a href="#" id="vrSearchViewAllLink" class="btn btn-sm btn-outline-dark rounded-pill px-4 fw-medium">
                    View all results &rarr;
                </a>
            </div>

        </div>
    </div>
</div>

<style>
.vr-search-dialog {
    max-width: 680px;
    width: 100%;
    margin: 1.5rem auto;
}
.vr-search-modal-content {
    border-radius: 16px;
    box-shadow: 0 20px 45px rgba(10, 29, 55, 0.2);
    max-height: calc(100vh - 3rem);
    max-height: calc(100dvh - 3rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #ffffff;
}
.vr-search-modal-body {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    flex: 1 1 auto;
    min-height: 0;
    max-height: min(60vh, 520px);
}
.vr-search-modal-body::-webkit-scrollbar {
    width: 5px;
}
.vr-search-modal-body::-webkit-scrollbar-track {
    background: transparent;
}
.vr-search-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.vr-search-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.vr-search-input-wrap {
    border-color: #e2e8f0 !important;
    background: #f8fafc;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.vr-search-input-wrap:focus-within {
    border-color: #15803d !important;
    box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.12) !important;
    background: #ffffff;
}
.vr-search-item {
    transition: background 0.15s ease, border-color 0.15s ease;
    border-radius: 10px;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #1e293b;
    border: 1px solid transparent;
    width: 100%;
    box-sizing: border-box;
    background: #ffffff;
}
.vr-search-item:hover, .vr-search-item:focus {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #0f172a;
}
@media (min-width: 576px) {
    .vr-search-item:hover, .vr-search-item:focus {
        transform: translateX(3px);
    }
}
.vr-search-thumb {
    width: 46px;
    height: 46px;
    border-radius: 8px;
    object-fit: cover;
    background: #f1f5f9;
    flex-shrink: 0;
}
.vr-search-item-info {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
}
.vr-search-item-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    margin-bottom: 2px;
}
.vr-search-item-cat {
    font-size: 0.72rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.vr-search-item-pricing {
    flex: 0 0 auto;
    text-align: right;
    white-space: nowrap;
    margin-left: 8px;
}
.vr-search-price-sale {
    font-size: 0.875rem;
    font-weight: 700;
    color: #15803d;
    line-height: 1.2;
}
.vr-search-price-mrp {
    font-size: 0.72rem;
    color: #94a3b8;
    text-decoration: line-through;
    line-height: 1.2;
}
.vr-search-cat-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 9999px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 0.76rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.15s ease;
    white-space: nowrap;
    max-width: 100%;
}
.vr-search-cat-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
    border-color: #cbd5e1;
}

@media (max-width: 575.98px) {
    .vr-search-dialog {
        margin: 0.5rem auto;
        width: calc(100% - 1rem);
        max-width: 100%;
    }
    .vr-search-modal-content {
        border-radius: 14px;
        max-height: calc(100dvh - 1rem);
    }
    .vr-search-modal-body {
        max-height: calc(100dvh - 185px);
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    .vr-search-thumb {
        width: 40px;
        height: 40px;
    }
    .vr-search-item {
        gap: 8px;
        padding: 6px 8px;
    }
    .vr-search-item-title {
        font-size: 0.8rem;
    }
    .vr-search-price-sale {
        font-size: 0.82rem;
    }
}
</style>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var searchInput = document.getElementById('vrSearchInput');
    var searchSpinner = document.getElementById('vrSearchSpinner');
    var resultsContainer = document.getElementById('vrSearchResultsContainer');
    var categoriesWrap = document.getElementById('vrSearchCategoriesWrap');
    var categoryPills = document.getElementById('vrSearchCategoryPills');
    var productsWrap = document.getElementById('vrSearchProductsWrap');
    var productsList = document.getElementById('vrSearchProductsList');
    var viewAllWrap = document.getElementById('vrSearchViewAllWrap');
    var viewAllLink = document.getElementById('vrSearchViewAllLink');
    var emptyState = document.getElementById('vrSearchEmptyState');
    var noResults = document.getElementById('vrSearchNoResults');
    var searchModalEl = document.getElementById('vrSearchModal');

    if (!searchInput) return;

    var debounceTimer = null;

    searchModalEl.addEventListener('shown.bs.modal', function () {
        searchInput.focus();
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function handleSearchInput() {
        var query = searchInput.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsContainer.classList.add('d-none');
            noResults.classList.add('d-none');
            emptyState.classList.remove('d-none');
            if (viewAllWrap) {
                viewAllWrap.classList.remove('d-flex');
                viewAllWrap.classList.add('d-none');
            }
            searchSpinner.classList.remove('d-flex');
            searchSpinner.classList.add('d-none');
            return;
        }

        searchSpinner.classList.remove('d-none');
        searchSpinner.classList.add('d-flex');

        debounceTimer = setTimeout(function () {
            fetch('{{ route("search.suggest") }}?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                searchSpinner.classList.remove('d-flex');
                searchSpinner.classList.add('d-none');
                renderResults(data, query);
            })
            .catch(function () {
                searchSpinner.classList.remove('d-flex');
                searchSpinner.classList.add('d-none');
            });
        }, 180);
    }

    searchInput.addEventListener('input', handleSearchInput);
    searchInput.addEventListener('search', handleSearchInput);

    function renderResults(data, query) {
        if (searchInput.value.trim() !== query) {
            return;
        }

        var hasProducts = data && Array.isArray(data.products) && data.products.length > 0;
        var hasCategories = data && Array.isArray(data.categories) && data.categories.length > 0;

        emptyState.classList.add('d-none');

        if (!hasProducts && !hasCategories) {
            resultsContainer.classList.add('d-none');
            noResults.classList.remove('d-none');
            if (viewAllWrap) {
                viewAllWrap.classList.remove('d-flex');
                viewAllWrap.classList.add('d-none');
            }
            return;
        }

        noResults.classList.add('d-none');
        resultsContainer.classList.remove('d-none');

        // Render categories
        if (hasCategories) {
            categoryPills.innerHTML = '';
            data.categories.forEach(function (cat) {
                var a = document.createElement('a');
                a.href = cat.url;
                a.className = 'vr-search-cat-pill';
                a.textContent = cat.name;
                categoryPills.appendChild(a);
            });
            categoriesWrap.classList.remove('d-none');
        } else {
            categoriesWrap.classList.add('d-none');
        }

        // Render products
        if (hasProducts) {
            productsList.innerHTML = '';
            data.products.forEach(function (p) {
                var a = document.createElement('a');
                a.href = p.url;
                a.className = 'vr-search-item';

                var imgHtml = p.image
                    ? '<img src="' + escapeHtml(p.image) + '" alt="' + escapeHtml(p.name) + '" class="vr-search-thumb">'
                    : '<div class="vr-search-thumb d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div>';

                var stockBadge = p.in_stock
                    ? ''
                    : '<span class="badge bg-danger ms-1" style="font-size: 10px;">Out of stock</span>';

                a.innerHTML = imgHtml +
                    '<div class="vr-search-item-info">' +
                        '<div class="vr-search-item-title">' + escapeHtml(p.name) + '</div>' +
                        (p.category ? '<div class="vr-search-item-cat">' + escapeHtml(p.category) + '</div>' : '') +
                    '</div>' +
                    '<div class="vr-search-item-pricing">' +
                        '<div class="vr-search-price-sale">' + escapeHtml(p.price) + '</div>' +
                        (p.mrp ? '<div class="vr-search-price-mrp">' + escapeHtml(p.mrp) + '</div>' : '') +
                        stockBadge +
                    '</div>';

                productsList.appendChild(a);
            });
            productsWrap.classList.remove('d-none');
        } else {
            productsWrap.classList.add('d-none');
        }

        // View All link
        if (viewAllWrap && viewAllLink) {
            viewAllLink.href = '{{ route("shop.index") }}?q=' + encodeURIComponent(query);
            viewAllLink.textContent = 'View all results for "' + query + '" →';
            viewAllWrap.classList.remove('d-none');
            viewAllWrap.classList.add('d-flex');
        }
    }
})();
</script>
