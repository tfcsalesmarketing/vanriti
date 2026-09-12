<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];

        // Home
        $urls[] = [
            'loc' => route('home'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Shop
        $urls[] = [
            'loc' => route('shop.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        // Products
        Product::query()->active()->select('slug', 'updated_at')->each(function (Product $p) use (&$urls) {
            $urls[] = [
                'loc' => route('product.show', $p),
                'lastmod' => $p->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
        });

        // Categories (root + children)
        Category::query()->whereNull('parent_id')->active()->select('slug', 'updated_at')->each(function (Category $c) use (&$urls) {
            $urls[] = [
                'loc' => route('shop.category', $c),
                'lastmod' => $c->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        });
        Category::query()->whereNotNull('parent_id')->active()->select('slug', 'updated_at')->each(function (Category $c) use (&$urls) {
            $urls[] = [
                'loc' => route('shop.category', $c),
                'lastmod' => $c->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });

        // Blog list + posts
        $urls[] = [
            'loc' => route('blog.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ];
        Blog::published()->select('slug', 'updated_at', 'published_at')->each(function (Blog $b) use (&$urls) {
            $urls[] = [
                'loc' => route('blog.show', $b),
                'lastmod' => $b->updated_at?->toAtomString() ?? $b->published_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        });

        // Info / utility pages
        foreach (['info.about', 'info.privacy', 'info.terms', 'info.shipping', 'info.disclaimer'] as $route) {
            $urls[] = [
                'loc' => route($route),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ];
        }
        $urls[] = [
            'loc' => route('shop.return-policy'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.4',
        ];
        $urls[] = [
            'loc' => route('shop.cancellation-policy'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.4',
        ];
        $urls[] = [
            'loc' => route('faq.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ];
        $urls[] = [
            'loc' => route('contact.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.4',
        ];
        $urls[] = [
            'loc' => route('track'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.3',
        ];

        // Remaining CMS pages that don't have a dedicated pretty route
        Page::published()->select('slug', 'updated_at')->each(function (Page $p) use (&$urls) {
            $pretty = [
                'about-us' => 'info.about',
                'privacy-policy' => 'info.privacy',
                'terms-and-conditions' => 'info.terms',
                'shipping-and-delivery' => 'info.shipping',
                'return-policy' => 'shop.return-policy',
                'cancellation-policy' => 'shop.cancellation-policy',
                'disclaimer' => 'info.disclaimer',
            ];

            if (isset($pretty[$p->slug])) {
                return; // already added above with its pretty URL
            }

            $urls[] = [
                'loc' => route('page', $p),
                'lastmod' => $p->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ];
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}