<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceClick;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceClickController extends Controller
{
    /**
     * Display a listing of all SKUs with Amazon and Flipkart click metrics and filters.
     */
    public function index(Request $request)
    {
        [$startDate, $endDate, $dateRangeLabel] = $this->resolveDateRange($request);

        // Calculate KPI summary metrics for the selected date range
        $kpis = $this->calculateKpis($startDate, $endDate);

        // Build product query with aggregated clicks
        $query = $this->buildReportQuery($request, $startDate, $endDate);

        $perPageInput = $request->input('per_page', '25');
        $perPage = ($perPageInput === 'all' || $perPageInput === '1000') ? 1000 : (int) $perPageInput;
        if ($perPage < 10) {
            $perPage = 25;
        }

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.marketplace_clicks.index', [
            'products' => $products,
            'categories' => $categories,
            'kpis' => $kpis,
            'dateRangeLabel' => $dateRangeLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filters' => [
                'search' => $request->input('search', ''),
                'category_id' => $request->input('category_id', ''),
                'marketplace_link' => $request->input('marketplace_link', 'all'),
                'activity' => $request->input('activity', 'all'),
                'date_range' => $request->input('date_range', 'all'),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'status' => $request->input('status', 'all'),
                'stock' => $request->input('stock', 'all'),
                'sort' => $request->input('sort', 'total_desc'),
                'per_page' => $perPageInput,
            ],
        ]);
    }

    /**
     * Export the filtered SKU marketplace click report to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        [$startDate, $endDate, $dateRangeLabel] = $this->resolveDateRange($request);
        $query = $this->buildReportQuery($request, $startDate, $endDate);
        $products = $query->get();

        $filename = 'marketplace_clicks_report_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($products) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, [
                'Product ID',
                'SKU',
                'Product Name',
                'Categories',
                'Status',
                'Stock',
                'Amazon Configured',
                'Amazon URL',
                'Amazon Clicks',
                'Flipkart Configured',
                'Flipkart URL',
                'Flipkart Clicks',
                'Total Clicks',
                'Last Clicked At',
            ]);

            foreach ($products as $product) {
                $categoriesStr = $product->categories->pluck('name')->implode(', ');
                $hasAmazon = filled($product->amazon_url) ? 'Yes' : 'No';
                $hasFlipkart = filled($product->flipkart_url) ? 'Yes' : 'No';
                $lastClicked = $product->last_clicked_at ? Carbon::parse($product->last_clicked_at)->format('Y-m-d H:i:s') : 'Never';

                fputcsv($output, [
                    $product->id,
                    $product->sku ?: '—',
                    $product->name,
                    $categoriesStr ?: 'Uncategorized',
                    ucfirst((string) $product->status),
                    $product->stock,
                    $hasAmazon,
                    $product->amazon_url ?: '',
                    (int) $product->amazon_clicks_count,
                    $hasFlipkart,
                    $product->flipkart_url ?: '',
                    (int) $product->flipkart_clicks_count,
                    (int) $product->total_clicks_count,
                    $lastClicked,
                ]);
            }

            fclose($output);
        }, $filename, $headers);
    }

    /**
     * Parse date range inputs.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    protected function resolveDateRange(Request $request): array
    {
        $preset = $request->input('date_range', 'all');
        $startDate = null;
        $endDate = null;
        $label = 'All Time';

        switch ($preset) {
            case 'today':
                $startDate = Carbon::today()->startOfDay();
                $endDate = Carbon::today()->endOfDay();
                $label = 'Today (' . $startDate->format('d M Y') . ')';
                break;

            case 'yesterday':
                $startDate = Carbon::yesterday()->startOfDay();
                $endDate = Carbon::yesterday()->endOfDay();
                $label = 'Yesterday (' . $startDate->format('d M Y') . ')';
                break;

            case '7days':
                $startDate = Carbon::now()->subDays(6)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $label = 'Last 7 Days (' . $startDate->format('d M') . ' - ' . $endDate->format('d M Y') . ')';
                break;

            case '30days':
                $startDate = Carbon::now()->subDays(29)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $label = 'Last 30 Days (' . $startDate->format('d M') . ' - ' . $endDate->format('d M Y') . ')';
                break;

            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $label = 'This Month (' . $startDate->format('F Y') . ')';
                break;

            case 'custom':
                if ($from = $request->input('date_from')) {
                    try {
                        $startDate = Carbon::parse($from)->startOfDay();
                    } catch (\Throwable $e) {
                        $startDate = null;
                    }
                }
                if ($to = $request->input('date_to')) {
                    try {
                        $endDate = Carbon::parse($to)->endOfDay();
                    } catch (\Throwable $e) {
                        $endDate = null;
                    }
                }
                if ($startDate && $endDate) {
                    $label = $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y');
                } elseif ($startDate) {
                    $label = 'From ' . $startDate->format('d M Y');
                } elseif ($endDate) {
                    $label = 'Until ' . $endDate->format('d M Y');
                } else {
                    $label = 'All Time';
                }
                break;

            default:
                $preset = 'all';
                $startDate = null;
                $endDate = null;
                $label = 'All Time';
                break;
        }

        return [$startDate, $endDate, $label];
    }

    /**
     * Calculate global KPIs for the selected timeframe.
     */
    protected function calculateKpis(?Carbon $startDate, ?Carbon $endDate): array
    {
        $clicksQuery = MarketplaceClick::query();
        if ($startDate) {
            $clicksQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $clicksQuery->where('created_at', '<=', $endDate);
        }

        $totalClicks = (clone $clicksQuery)->count();
        $amazonClicks = (clone $clicksQuery)->where('marketplace', 'amazon')->count();
        $flipkartClicks = (clone $clicksQuery)->where('marketplace', 'flipkart')->count();
        $clickedSkusCount = (clone $clicksQuery)->distinct('product_id')->count('product_id');

        $totalCatalogCount = Product::query()->count();
        $totalWithLinks = Product::query()
            ->where(function ($q) {
                $q->where(fn ($sub) => $sub->whereNotNull('amazon_url')->where('amazon_url', '!=', ''))
                    ->orWhere(fn ($sub) => $sub->whereNotNull('flipkart_url')->where('flipkart_url', '!=', ''));
            })->count();

        $amazonShare = $totalClicks > 0 ? round(($amazonClicks / $totalClicks) * 100, 1) : 0;
        $flipkartShare = $totalClicks > 0 ? round(($flipkartClicks / $totalClicks) * 100, 1) : 0;
        $skuEngagementRate = $totalWithLinks > 0 ? round(($clickedSkusCount / $totalWithLinks) * 100, 1) : 0;

        return [
            'total_clicks' => $totalClicks,
            'amazon_clicks' => $amazonClicks,
            'amazon_share' => $amazonShare,
            'flipkart_clicks' => $flipkartClicks,
            'flipkart_share' => $flipkartShare,
            'clicked_skus_count' => $clickedSkusCount,
            'total_with_links' => $totalWithLinks,
            'total_catalog' => $totalCatalogCount,
            'sku_engagement_rate' => $skuEngagementRate,
        ];
    }

    /**
     * Build the query for SKU report rows.
     */
    protected function buildReportQuery(Request $request, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        // Aggregate clicks per product for the date range
        $sub = DB::table('marketplace_clicks')
            ->select('product_id')
            ->selectRaw("COUNT(CASE WHEN marketplace = 'amazon' THEN 1 END) as amazon_clicks_count")
            ->selectRaw("COUNT(CASE WHEN marketplace = 'flipkart' THEN 1 END) as flipkart_clicks_count")
            ->selectRaw("COUNT(*) as total_clicks_count")
            ->selectRaw("MAX(created_at) as last_clicked_at");

        if ($startDate) {
            $sub->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $sub->where('created_at', '<=', $endDate);
        }
        $sub->groupBy('product_id');

        $query = Product::query()
            ->leftJoinSub($sub, 'agg_clicks', function ($join) {
                $join->on('products.id', '=', 'agg_clicks.product_id');
            })
            ->select('products.*')
            ->selectRaw('COALESCE(agg_clicks.amazon_clicks_count, 0) as amazon_clicks_count')
            ->selectRaw('COALESCE(agg_clicks.flipkart_clicks_count, 0) as flipkart_clicks_count')
            ->selectRaw('COALESCE(agg_clicks.total_clicks_count, 0) as total_clicks_count')
            ->selectRaw('agg_clicks.last_clicked_at as last_clicked_at')
            ->with(['images', 'categories']);

        // 1. Text Search Filter (name, sku, slug)
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.slug', 'like', "%{$search}%");
            });
        }

        // 2. Category Filter
        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // 3. Marketplace Link Filter
        $mktLink = $request->input('marketplace_link', 'all');
        if ($mktLink === 'has_amazon') {
            $query->whereNotNull('products.amazon_url')->where('products.amazon_url', '!=', '');
        } elseif ($mktLink === 'has_flipkart') {
            $query->whereNotNull('products.flipkart_url')->where('products.flipkart_url', '!=', '');
        } elseif ($mktLink === 'has_both') {
            $query->whereNotNull('products.amazon_url')->where('products.amazon_url', '!=', '')
                ->whereNotNull('products.flipkart_url')->where('products.flipkart_url', '!=', '');
        } elseif ($mktLink === 'has_any') {
            $query->where(function ($q) {
                $q->where(fn ($sub) => $sub->whereNotNull('products.amazon_url')->where('products.amazon_url', '!=', ''))
                    ->orWhere(fn ($sub) => $sub->whereNotNull('products.flipkart_url')->where('products.flipkart_url', '!=', ''));
            });
        } elseif ($mktLink === 'has_none') {
            $query->where(function ($q) {
                $q->whereNull('products.amazon_url')->orWhere('products.amazon_url', '=', '');
            })->where(function ($q) {
                $q->whereNull('products.flipkart_url')->orWhere('products.flipkart_url', '=', '');
            });
        }

        // 4. Click Activity Filter
        $activity = $request->input('activity', 'all');
        if ($activity === 'with_clicks') {
            $query->whereRaw('COALESCE(agg_clicks.total_clicks_count, 0) > 0');
        } elseif ($activity === 'zero_clicks') {
            $query->whereRaw('COALESCE(agg_clicks.total_clicks_count, 0) = 0');
        } elseif ($activity === 'amazon_only') {
            $query->whereRaw('COALESCE(agg_clicks.amazon_clicks_count, 0) > 0');
        } elseif ($activity === 'flipkart_only') {
            $query->whereRaw('COALESCE(agg_clicks.flipkart_clicks_count, 0) > 0');
        }

        // 5. Product Status Filter
        $status = $request->input('status', 'all');
        if ($status === 'active') {
            $query->where('products.status', 'active');
        } elseif ($status === 'inactive') {
            $query->where('products.status', '!=', 'active');
        }

        // 6. Stock Status Filter
        $stock = $request->input('stock', 'all');
        if ($stock === 'in_stock') {
            $query->where('products.stock', '>', 0);
        } elseif ($stock === 'out_of_stock') {
            $query->where('products.stock', '<=', 0);
        }

        // 7. Sorting
        $sort = $request->input('sort', 'total_desc');
        switch ($sort) {
            case 'total_asc':
                $query->orderBy('total_clicks_count', 'asc')->orderBy('products.name', 'asc');
                break;
            case 'amazon_desc':
                $query->orderBy('amazon_clicks_count', 'desc')->orderBy('total_clicks_count', 'desc');
                break;
            case 'flipkart_desc':
                $query->orderBy('flipkart_clicks_count', 'desc')->orderBy('total_clicks_count', 'desc');
                break;
            case 'recent_desc':
                $query->orderByRaw('agg_clicks.last_clicked_at IS NULL, agg_clicks.last_clicked_at DESC');
                break;
            case 'sku_asc':
                $query->orderBy('products.sku', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('products.name', 'asc');
                break;
            case 'total_desc':
            default:
                $query->orderBy('total_clicks_count', 'desc')->orderBy('products.name', 'asc');
                break;
        }

        return $query;
    }
}

