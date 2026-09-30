<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * CMS pages that already have a dedicated pretty route. Each is emitted once
     * under that pretty URL and skipped by the generic /pages/{slug} pass.
     */
    private const PRETTY_PAGE_ROUTES = [
        'about-us' => 'info.about',
        'privacy-policy' => 'info.privacy',
        'terms-and-conditions' => 'info.terms',
        'shipping-and-delivery' => 'info.shipping',
        'return-policy' => 'shop.return-policy',
        'cancellation-policy' => 'shop.cancellation-policy',
        'disclaimer' => 'info.disclaimer',
    ];

    public function __invoke(): Response
    {
        $urls = [];

        // Only real modification timestamps are emitted. A now() value changes on
        // every crawl, which tells Google the sitemap carries no recrawl signal.
        $entry = static fn (string $loc, $modifiedAt = null): array => [
            'loc' => $loc,
            'lastmod' => $modifiedAt ? Carbon::parse($modifiedAt)->toAtomString() : null,
        ];

        $homeLastmod = Product::query()->active()->max('updated_at');
        $urls[] = $entry(route('home'), $homeLastmod);

        // Shop - no content timestamp of its own
        $urls[] = $entry(route('shop.index'));

        // Products
        Product::query()->active()->select('slug', 'updated_at')->each(function (Product $p) use (&$urls, $entry) {
            $urls[] = $entry(route('product.show', $p), $p->updated_at);
        });

        // Categories (root + children)
        Category::query()->whereNull('parent_id')->active()->select('slug', 'updated_at')->each(function (Category $c) use (&$urls, $entry) {
            $urls[] = $entry(route('shop.category', $c), $c->updated_at);
        });
        Category::query()->whereNotNull('parent_id')->active()->select('slug', 'updated_at')->each(function (Category $c) use (&$urls, $entry) {
            $urls[] = $entry(route('shop.category', $c), $c->updated_at);
        });

        // Blog list - posts carry their own timestamps
        $urls[] = $entry(route('blog.index'));
        Blog::published()->select('slug', 'updated_at', 'published_at')->each(function (Blog $b) use (&$urls, $entry) {
            $urls[] = $entry(route('blog.show', $b), $b->updated_at ?? $b->published_at);
        });

        // Info / utility pages backed by a CMS row reuse that row's timestamp
        $pageLastmod = Page::published()
            ->whereIn('slug', array_keys(self::PRETTY_PAGE_ROUTES))
            ->pluck('updated_at', 'slug');

        $prettyEntry = static function (string $route) use ($pageLastmod, $entry): array {
            $slug = array_search($route, self::PRETTY_PAGE_ROUTES, true);

            return $entry(route($route), $pageLastmod[$slug] ?? null);
        };

        foreach (['info.about', 'info.privacy', 'info.terms', 'info.shipping', 'info.disclaimer'] as $route) {
            $urls[] = $prettyEntry($route);
        }
        foreach (['shop.return-policy', 'shop.cancellation-policy'] as $route) {
            $urls[] = $prettyEntry($route);
        }

        $urls[] = $entry(route('faq.index'));
        $urls[] = $entry(route('contact.index'));

        // Remaining CMS pages that don't have a dedicated pretty route
        Page::published()->select('slug', 'updated_at')->each(function (Page $p) use (&$urls, $entry) {
            if (isset(self::PRETTY_PAGE_ROUTES[$p->slug])) {
                return; // already emitted above under its pretty URL
            }

            $urls[] = $entry(route('page', $p), $p->updated_at);
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $item) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($item['loc']).'</loc>'."\n";
            if ($item['lastmod'] !== null) {
                $xml .= '    <lastmod>'.$item['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
