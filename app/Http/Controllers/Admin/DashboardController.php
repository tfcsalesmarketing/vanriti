<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = $this->computeStats();
        $charts = $this->chartData();

        return view('admin.dashboard', compact('stats', 'charts'));
    }

    private function computeStats(): array
    {
        $today = now()->toDateString();

        $ordersTable = Schema::hasTable('orders');

        return [
            'total_sales' => $ordersTable ? (float) Order::whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total') : 0.0,
            'today_sales' => $ordersTable ? (float) Order::whereDate('created_at', $today)->whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total') : 0.0,
            'monthly_sales' => $ordersTable ? (float) Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->whereNotIn('order_status', ['cancelled', 'failed'])->sum('grand_total') : 0.0,
            'total_orders' => $ordersTable ? Order::count() : 0,
            'pending_orders' => $ordersTable ? Order::where('order_status', 'pending')->count() : 0,
            'processing_orders' => $ordersTable ? Order::whereIn('order_status', ['confirmed', 'processing'])->count() : 0,
            'shipped_orders' => $ordersTable ? Order::whereIn('order_status', ['shipped', 'out_for_delivery'])->count() : 0,
            'delivered_orders' => $ordersTable ? Order::where('order_status', 'delivered')->count() : 0,
            'cancelled_orders' => $ordersTable ? Order::where('order_status', 'cancelled')->count() : 0,
            'refund_requests' => Schema::hasTable('return_requests') ? ReturnRequest::whereHas('refunds', fn ($q) => $q->whereIn('status', ['requested', 'under_review', 'approved', 'processing']))->count() : 0,
            'customers' => Schema::hasTable('users') ? User::count() : 0,
            'products' => Schema::hasTable('products') ? Product::query()->withoutGlobalScopes()->count() : 0,
            'low_stock' => Schema::hasTable('product_variants') ? DB::table('product_variants')->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_threshold')->count() : 0,
            'out_of_stock' => Schema::hasTable('product_variants') ? DB::table('product_variants')->where('stock', '<=', 0)->count() : 0,
            'pending_reviews' => Schema::hasTable('reviews') ? Review::where('status', 'pending')->count() : 0,
        ];
    }

    private function chartData(): array
    {
        $salesByDay = [];
        $ordersByDay = [];
        $salesByMonth = [];
        $topProducts = [];
        $categorySales = [];

        if (Schema::hasTable('orders')) {
            $salesByDay = Cache::remember('dashboard.sales.by.day', 600, function () {
                return DB::table('orders')
                    ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
                    ->where('created_at', '>=', now()->subDays(14))
                    ->whereNotIn('order_status', ['cancelled', 'failed'])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('total', 'date')
                    ->toArray();
            });

            $ordersByDay = DB::table('orders')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
                ->where('created_at', '>=', now()->subDays(14))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();

            $dateExpr = config('database.default') === 'sqlite'
                ? "strftime('%Y-%m', created_at)"
                : "DATE_FORMAT(created_at, '%Y-%m')";

            $salesByMonth = DB::table('orders')
                ->select(DB::raw("$dateExpr as month"), DB::raw('SUM(grand_total) as total'))
                ->where('created_at', '>=', now()->subMonths(12))
                ->whereNotIn('order_status', ['cancelled', 'failed'])
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            if (Schema::hasTable('order_items')) {
                $topProducts = DB::table('order_items')
                    ->select('product_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total_price) as total'))
                    ->groupBy('product_name')
                    ->orderByDesc('qty')
                    ->limit(8)
                    ->get();

                $categorySales = DB::table('order_items')
                    ->select('category_name', DB::raw('SUM(total_price) as total'))
                    ->whereNotNull('category_name')
                    ->groupBy('category_name')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get();
            }
        }

        return [
            'sales_by_day' => $salesByDay,
            'orders_by_day' => $ordersByDay,
            'sales_by_month' => $salesByMonth,
            'top_products' => $topProducts,
            'category_sales' => $categorySales,
        ];
    }
}