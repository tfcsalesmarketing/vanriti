<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use App\Models\Tfc\DirectSellingMember;
use App\Models\Tfc\MemberTreeNode;
use App\Services\Tfc\TfcGenealogyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberTeamController extends Controller
{
    protected TfcGenealogyService $genealogyService;

    public function __construct(TfcGenealogyService $genealogyService)
    {
        $this->genealogyService = $genealogyService;
    }

    /**
     * Visual Binary Tree Genealogy View.
     */
    public function tree(Request $request): View
    {
        $currentMember = Auth::user()->directSellingMember;
        $targetTfcId = trim((string) $request->query('root', ''));

        $rootMember = $currentMember;

        if ($targetTfcId !== '' && $targetTfcId !== $currentMember->tfc_member_id) {
            $searchedMember = DirectSellingMember::where('member_id', $targetTfcId)->first();
            // Subtree confinement: target must be inside current member's downline
            if ($searchedMember && $this->genealogyService->isDescendantOf($searchedMember->id, $currentMember->id)) {
                $rootMember = $searchedMember;
            }
        }

        // Build 3-level tree hierarchy starting from rootMember
        $treeData = $this->genealogyService->buildTreeHierarchy($rootMember->id, 3);
        $stats = $this->genealogyService->getTeamStatistics($rootMember->id);

        return view('tfc.member.team.tree', [
            'currentMember' => $currentMember,
            'rootMember' => $rootMember,
            'treeData' => $treeData,
            'stats' => $stats,
        ]);
    }

    /**
     * AJAX endpoint to fetch node details for tree modal/expansion.
     */
    public function ajaxNode(int $id): JsonResponse
    {
        $currentMember = Auth::user()->directSellingMember;

        // Subtree confinement check
        if ($id !== $currentMember->id && ! $this->genealogyService->isDescendantOf($id, $currentMember->id)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $node = DirectSellingMember::with(['sponsor', 'placementParent', 'user'])->find($id);
        if (! $node) {
            return response()->json(['error' => 'Node not found.'], 404);
        }

        $stats = $this->genealogyService->getTeamStatistics($node->id);

        return response()->json([
            'id' => $node->id,
            'tfc_member_id' => $node->tfc_member_id,
            'full_name' => $node->full_name,
            'sponsor_name' => $node->sponsor ? $node->sponsor->full_name . ' (' . $node->sponsor->tfc_member_id . ')' : 'Direct',
            'placement_parent_name' => $node->placementParent ? $node->placementParent->full_name . ' (' . $node->placementParent->tfc_member_id . ')' : 'Root',
            'placement_position' => ucfirst($node->placement_position ?? 'root'),
            'joined_at' => $node->joined_at ? $node->joined_at->format('d M Y') : 'N/A',
            'orange_team' => $stats['orange_team_count'],
            'blue_team' => $stats['blue_team_count'],
            'direct_referrals' => $stats['direct_referrals_count'],
        ]);
    }

    /**
     * Direct Sponsored Members Table.
     */
    public function directReferrals(Request $request): View
    {
        $currentMember = Auth::user()->directSellingMember;
        $search = trim((string) $request->query('q', ''));

        $query = DirectSellingMember::where('sponsor_id', $currentMember->id)
            ->with(['placementParent', 'user'])
            ->latest('registration_date');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('member_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $directs = $query->paginate(15)->withQueryString();

        return view('tfc.member.team.table', [
            'member' => $currentMember,
            'directs' => $directs,
            'search' => $search,
            'type' => 'direct',
            'title' => 'Direct Referrals',
        ]);
    }

    /**
     * Group Leg Explorer (Orange or Blue).
     */
    public function groupLeg(string $leg, Request $request): View
    {
        $leg = strtolower(trim($leg));
        if (! in_array($leg, ['orange', 'blue'], true)) {
            abort(404);
        }

        $currentMember = Auth::user()->directSellingMember;
        $search = trim((string) $request->query('q', ''));

        // Query descendants via closure table matching group_leg
        $query = MemberTreeNode::where('ancestor_id', $currentMember->id)
            ->where('group_leg', $leg)
            ->where('depth', '>', 0)
            ->join('direct_selling_members', 'member_tree_nodes.descendant_id', '=', 'direct_selling_members.id')
            ->select('direct_selling_members.*', 'member_tree_nodes.depth')
            ->with('user')
            ->orderBy('member_tree_nodes.depth', 'asc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('direct_selling_members.member_id', 'like', "%{$search}%");
            });
        }

        $members = $query->paginate(20)->withQueryString();

        return view('tfc.member.team.group', [
            'member' => $currentMember,
            'leg' => $leg,
            'members' => $members,
            'search' => $search,
        ]);
    }
}
