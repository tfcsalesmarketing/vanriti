<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\DirectSellingMember;
use App\Services\Tfc\TfcGenealogyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcMemberAdminController extends Controller
{
    protected TfcGenealogyService $genealogyService;

    public function __construct(TfcGenealogyService $genealogyService)
    {
        $this->genealogyService = $genealogyService;
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $leg = trim((string) $request->query('leg', ''));

        $query = DirectSellingMember::with(['sponsor', 'placementParent', 'user'])
            ->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('member_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status !== '' && in_array($status, ['active', 'inactive', 'suspended', 'terminated'], true)) {
            $query->where('membership_status', $status);
        }

        if ($leg !== '' && in_array($leg, ['orange', 'blue'], true)) {
            $query->where('placement_position', $leg);
        }

        $members = $query->paginate(20)->withQueryString();

        return view('admin.tfc.members.index', [
            'members' => $members,
            'search' => $search,
            'status' => $status,
            'leg' => $leg,
        ]);
    }

    public function show(int $id): View
    {
        $member = DirectSellingMember::with(['sponsor', 'placementParent', 'user', 'kyc'])
            ->findOrFail($id);

        $stats = $this->genealogyService->getTeamStatistics($member->id);

        return view('admin.tfc.members.show', [
            'member' => $member,
            'stats' => $stats,
        ]);
    }

    public function updateStatus(int $id, Request $request): RedirectResponse
    {
        $member = DirectSellingMember::findOrFail($id);

        $request->validate([
            'status' => ['required', 'in:active,inactive,suspended,terminated'],
        ]);

        $newStatus = (string) $request->input('status');
        $member->update(['membership_status' => $newStatus]);

        return back()->with('success', "Member {$member->tfc_member_id} status updated to " . ucfirst($newStatus) . '.');
    }
}
