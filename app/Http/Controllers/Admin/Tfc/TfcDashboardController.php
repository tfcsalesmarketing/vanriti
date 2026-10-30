<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tfc\TfcBanner;
use App\Models\Tfc\TfcBlog;
use App\Models\Tfc\TfcGrievanceTicket;
use App\Models\Tfc\TfcInquiry;
use App\Models\Tfc\TfcPage;
use Illuminate\View\View;

class TfcDashboardController extends Controller
{
    /**
     * TFC Admin Executive Dashboard.
     */
    public function index(): View
    {
        $stats = [
            'total_synced_products' => Product::active()->count(),
            'total_pages' => TfcPage::count(),
            'draft_pages' => TfcPage::draft()->count(),
            'published_blogs' => TfcBlog::published()->count(),
            'active_banners' => TfcBanner::active()->count(),
            'new_inquiries' => TfcInquiry::where('status', 'new')->count(),
            'pending_grievances' => TfcGrievanceTicket::where('status', '!=', 'resolved')->count(),
        ];

        $recentInquiries = TfcInquiry::latest()->take(5)->get();
        $recentGrievances = TfcGrievanceTicket::latest()->take(5)->get();
        $draftPages = TfcPage::draft()->get();

        return view('admin.tfc.dashboard', compact('stats', 'recentInquiries', 'recentGrievances', 'draftPages'));
    }
}
