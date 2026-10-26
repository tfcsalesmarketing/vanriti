{{-- Modern Predictive Search Modal --}}
<div class="modal fade vr-search-modal" id="vrSearchModal" tabindex="-1" aria-labelledby="vrSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header border-0 pb-0 pt-3 px-4 d-flex align-items-center justify-content-between">
                <span class="small text-uppercase fw-bold text-muted" id="vrSearchModalLabel" style="letter-spacing: 0.5px; font-size: 11px;">Search Vanriti Herbal &amp; Ayurvedic Care</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <form action="{{ route('shop.index') }}" method="GET" id="vrLiveSearchForm" class="mb-3">
                    <div class="input-group input-group-lg border rounded-3 p-1 shadow-xs" style="border-color: #e2e8f0 !important; background: #f8fafc;">
                        <span class="input-group-text bg-transparent border-0 text-muted ps-3">
                            <i class="bi bi-search fs-5 text-success"></i>
                        </span>
                        <input type="search" name="q" id="vrSearchInput" class="form-control bg-transparent border-0 fs-6"
                               placeholder="Search hair care, skin care, amla, beetroot, wellness..." autocomplete="off" autofocus>
                        <div class="d-none align-items-center pe-3" id="vrSearchSpinner">
                            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        </div>
                    </div>
                </form>

                {{-- Live Results Container --}}
                <div id="vrSearchResultsContainer" class="d-none">
                    {{-- Categories Match --}}
                    <div id="vrSearchCategoriesWrap" class="mb-3 d-none">
                        <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 11px;">Categories</div>
                        <div class="d-flex flex-wrap gap-2" id="vrSearchCategoryPills"></div>
                    </div>

                    {{-- Products Match --}}
                    <div id="vrSearchProductsWrap" class="d-none">
                        <div class="small fw-semibold text-muted text-uppercase mb-2" style="font-size: 11px;">Products</div>
                        <div class="d-grid gap-2" id="vrSearchProductsList"></div>
                    </div>

                    {{-- View All Results Footer Link --}}
                    <div class="text-center pt-3 border-top mt-3" id="vrSearchViewAllWrap">
                        <a href="#" id="vrSearchViewAllLink" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                            View all results &rarr;
                        </a>
                    </div>
                </div>

                {{-- Empty State / Initial Suggestions --}}
                <div id="vrSearchEmptyState" class="py-3 text-center text-muted">
                    <p class="small mb-2">Try searching for:</p>
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
                    <i class="bi bi-search fs-2 mb-2 d-block text-muted"></i>
                    <h6 class="fw-bold mb-1 text-dark">No matching items found</h6>
                    <p class="small mb-0">Try checking your spelling or searching for a broader term.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var searchInput = document.getElementById('vrSearchInput');
    var searchSpinner = document.getElementById('vrSearchSpinner');
    var resultsContainer = document.getElementById('vrSearchResultsContainer');
    var categoriesWrap = document.getElementById('vrSearchCategoriesWrap');
    var categoryPills = document.getElementById('vrSearchCategoryPills');
    var productsWrap = document.getElementById('vrSearchProductsWrap');
    var productsList = document.getElementById('vrSearchProductsList');
    var viewAllLink = document.getElementById('vrSearchViewAllLink');
    var emptyState = document.getElementById('vrSearchEmptyState');
    var noResults = document.getElementById('vrSearchNoResults');
    var searchModalEl = document.getElementById('vrSearchModal');

    if (!searchInput) return;

    var debounceTimer = null;

    searchModalEl.addEventListener('shown.bs.modal', function () {
        searchInput.focus();
    });

    searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 2) {
            resultsContainer.classList.add('d-none');
            noResults.classList.add('d-none');
            emptyState.classList.remove('d-none');
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
            .then(function (res) { return res.json(); })
            .then(function (data) {
                searchSpinner.classList.remove('d-flex');
                searchSpinner.classList.add('d-none');
                renderResults(data, query);
            })
            .catch(function () {
                searchSpinner.classList.remove('d-flex');
                searchSpinner.classList.add('d-none');
            });
        }, 220);
    });

    function renderResults(data, query) {
        var hasProducts = data.products && data.products.length > 0;
        var hasCategories = data.categories && data.categories.length > 0;

        emptyState.classList.add('d-none');

        if (!hasProducts && !hasCategories) {
            resultsContainer.classList.add('d-none');
            noResults.classList.remove('d-none');
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
                a.className = 'badge rounded-pill text-dark text-decoration-none px-3 py-2 border';
                a.style.background = '#f1f5f9';
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
                    ? '<img src="' + p.image + '" alt="' + p.name + '" class="vr-search-thumb">'
                    : '<div class="vr-search-thumb d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div>';

                var stockBadge = p.in_stock
                    ? ''
                    : '<span class="badge bg-danger ms-1" style="font-size: 10px;">Out of stock</span>';

                a.innerHTML = imgHtml +
                    '<div class="flex-grow-1 min-w-0">' +
                        '<div class="fw-semibold text-truncate small">' + p.name + '</div>' +
                        (p.category ? '<div class="text-muted" style="font-size: 11px;">' + p.category + '</div>' : '') +
                    '</div>' +
                    '<div class="text-end flex-shrink-0">' +
                        '<div class="fw-bold small text-success">' + p.price + '</div>' +
                        (p.mrp ? '<div class="text-muted text-decoration-line-through" style="font-size: 11px;">' + p.mrp + '</div>' : '') +
                        stockBadge +
                    '</div>';

                productsList.appendChild(a);
            });
            productsWrap.classList.remove('d-none');
        } else {
            productsWrap.classList.add('d-none');
        }

        // View All link
        viewAllLink.href = '{{ route("shop.index") }}?q=' + encodeURIComponent(query);
        viewAllLink.textContent = 'View all results for "' + query + '" →';
    }
})();
</script>
