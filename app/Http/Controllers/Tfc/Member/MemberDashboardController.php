<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use App\Models\Tfc\DirectSellingMember;
use App\Services\Tfc\TfcGenealogyService;
use App\Services\Tfc\TfcNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    protected TfcGenealogyService $genealogyService;
    protected TfcNotificationService $notificationService;

    public function __construct(
        TfcGenealogyService $genealogyService,
        TfcNotificationService $notificationService
    ) {
        $this->genealogyService = $genealogyService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): View
    {
        $member = Auth::user()->directSellingMember;
        $stats = $this->genealogyService->getTeamStatistics($member->id);

        $referralUrl = $this->notificationService->generateReferralUrl($member);

        // Recent 5 direct referrals
        $recentDirects = DirectSellingMember::where('sponsor_id', $member->id)
            ->with('user')
            ->latest('created_at')
            ->take(5)
            ->get();

        // Immediate placement children
        $orangeChild = $member->orangeChild;
        $blueChild = $member->blueChild;

        return view('tfc.member.dashboard', [
            'member' => $member,
            'stats' => $stats,
            'referralUrl' => $referralUrl,
            'recentDirects' => $recentDirects,
            'orangeChild' => $orangeChild,
            'blueChild' => $blueChild,
        ]);
    }
}
