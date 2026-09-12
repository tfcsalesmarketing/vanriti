@if (! empty($cards))
    <div class="dadi-recs" aria-label="{{ count($cards) > 1 ? 'Recommended products' : 'Recommended product' }}">
        @foreach ($cards as $_card)
            <div class="dadi-card"
                data-dadi-ref="{{ $_card['reference'] ?? '' }}"
                data-add-url="{{ $_card['add_url'] ?? '' }}"
                data-variant-id="{{ $_card['variant_id'] ?? '' }}">
                <a class="dadi-card-media" href="{{ $_card['product_url'] }}" aria-label="{{ 'Dekho: '.$_card['name'] }}" data-dadi-click>
                    <img
                        class="dadi-card-img"
                        src="{{ $_card['image_url'] }}"
                        alt=""
                        loading="lazy"
                    >
                </a>
                <div class="dadi-card-body">
                    <span class="dadi-card-name">{{ $_card['name'] }}</span>
                    <span class="dadi-card-price">
                        <span class="dadi-card-now">{{ $_card['price_formatted'] }}</span>
                        @if ((float) $_card['mrp'] > (float) $_card['price'])
                            <del class="dadi-card-mrp">{{ $_card['mrp_formatted'] }}</del>
                        @endif
                    </span>
                    <span class="dadi-card-meta">
                        <span class="dadi-card-stock {{ $_card['available'] ? 'is-available' : 'is-unavailable' }}">
                            {{ $_card['available'] ? 'Stock mein hai' : 'Abhi stock khatam' }}
                        </span>
                    </span>
                    @if (! empty($_card['why']))
                        <p class="dadi-card-why">{{ $_card['why'] }}</p>
                    @endif
                </div>
                @if (! empty($_card['addable']) && ! empty($_card['add_url']))
                    <div class="dadi-card-actions">
                        <button type="button" class="dadi-card-btn js-dadi-add">Cart mein rakho</button>
                        <button type="button" class="dadi-card-btn dadi-card-btn-primary js-dadi-buy-now">Abhi kharidein</button>
                        <span class="dadi-card-actions-row">
                            <a class="dadi-card-link" href="{{ $_card['product_url'] }}" data-dadi-click>Dekho <i class="ri-arrow-right-line"></i></a>
                        </span>
                    </div>
                @else
                    <div class="dadi-card-actions">
                        <span class="dadi-card-actions-row">
                            <a class="dadi-card-link" href="{{ $_card['product_url'] }}" data-dadi-click>Dekho <i class="ri-arrow-right-line"></i></a>
                        </span>
                    </div>
                @endif
                <p class="dadi-card-status" hidden data-dadi-status aria-live="polite"></p>
            </div>
        @endforeach
    </div>
@endif