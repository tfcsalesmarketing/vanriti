<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\DirectSellingMember;
use App\Models\Tfc\MemberKyc;
use App\Models\Tfc\TfcGrievanceTicket;
use App\Models\Tfc\TfcInquiry;
use App\Models\WhatsAppConversation;
use Illuminate\View\View;

class TfcDashboardController extends Controller
{
    /**
     * TFC Admin Operations Dashboard.
     */
    public function index(): View
    {
        $stats = [
            'total_members' => DirectSellingMember::count(),
            'active_members' => DirectSellingMember::where('membership_status', 'active')->count(),
            'pending_kyc' => MemberKyc::where('status', 'pending')->count(),
            'new_inquiries' => TfcInquiry::where('status', 'new')->count(),
            'pending_grievances' => TfcGrievanceTicket::where('status', '!=', 'resolved')->count(),
            'tfc_wa_conversations' => WhatsAppConversation::where('channel', 'tfc')->count(),
        ];

        $recentMembers = DirectSellingMember::with(['user', 'sponsor'])->latest('created_at')->take(5)->get();
        $pendingKycs = MemberKyc::with(['member.user'])->where('status', 'pending')->latest('submitted_at')->take(5)->get();
        $recentInquiries = TfcInquiry::latest()->take(5)->get();

        return view('admin.tfc.dashboard', compact('stats', 'recentMembers', 'pendingKycs', 'recentInquiries'));
    }
}
