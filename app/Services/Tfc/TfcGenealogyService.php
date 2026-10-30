<?php

namespace App\Services\Tfc;

use App\Models\Tfc\DirectSellingMember;
use App\Models\Tfc\MemberTreeNode;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TfcGenealogyService
{
    /**
     * Look up and validate a Sponsor by public Member ID.
     */
    public function validateSponsor(string $sponsorMemberId): array
    {
        $cleanId = strtoupper(trim($sponsorMemberId));
        if (empty($cleanId)) {
            return [
                'valid' => false,
                'message' => 'Sponsor ID is required.',
            ];
        }

        $sponsor = DirectSellingMember::with('user')
            ->where('member_id', $cleanId)
            ->first();

        if (! $sponsor) {
            return [
                'valid' => false,
                'message' => "Sponsor ID {$cleanId} not found in TFC system.",
            ];
        }

        if ($sponsor->membership_status === 'terminated') {
            return [
                'valid' => false,
                'message' => 'This sponsor account is terminated and ineligible to sponsor.',
            ];
        }

        return [
            'valid' => true,
            'id' => $sponsor->id,
            'member_id' => $sponsor->member_id,
            'name' => $sponsor->display_name,
            'status' => $sponsor->membership_status,
        ];
    }

    /**
     * Look up and validate a Placement Parent by public Member ID.
     * Checks availability of Orange and Blue legs.
     */
    public function validatePlacementParent(string $parentMemberId, ?string $sponsorMemberId = null): array
    {
        $cleanParentId = strtoupper(trim($parentMemberId));
        if (empty($cleanParentId)) {
            return [
                'valid' => false,
                'message' => 'Placement Parent ID is required.',
            ];
        }

        $parent = DirectSellingMember::with(['user', 'placementChildren'])
            ->where('member_id', $cleanParentId)
            ->first();

        if (! $parent) {
            return [
                'valid' => false,
                'message' => "Placement Parent ID {$cleanParentId} not found in TFC system.",
            ];
        }

        if ($parent->membership_status === 'terminated') {
            return [
                'valid' => false,
                'message' => 'This placement parent account is terminated and ineligible for placements.',
            ];
        }

        $occupiedPositions = $parent->placementChildren->pluck('placement_position')->toArray();
        $orangeAvailable = ! in_array('orange', $occupiedPositions, true);
        $blueAvailable = ! in_array('blue', $occupiedPositions, true);

        // Subtree Confinement Check: If Sponsor ID is given, placement parent must either be the sponsor or in sponsor's downline!
        $isWithinSponsorDownline = true;
        if (! empty($sponsorMemberId)) {
            $cleanSponsorId = strtoupper(trim($sponsorMemberId));
            $sponsor = DirectSellingMember::where('member_id', $cleanSponsorId)->first();
            if ($sponsor && $sponsor->id !== $parent->id) {
                $isWithinSponsorDownline = $this->isDescendantOf($parent->id, $sponsor->id);
            }
        }

        if (! $isWithinSponsorDownline) {
            return [
                'valid' => false,
                'message' => 'Placement Parent must be the sponsor or a member within the sponsor\'s team tree.',
                'orange_available' => false,
                'blue_available' => false,
            ];
        }

        return [
            'valid' => true,
            'id' => $parent->id,
            'member_id' => $parent->member_id,
            'name' => $parent->display_name,
            'status' => $parent->membership_status,
            'orange_available' => $orangeAvailable,
            'blue_available' => $blueAvailable,
            'has_available_slots' => $orangeAvailable || $blueAvailable,
        ];
    }

    /**
     * Check if candidate member is a descendant of ancestor in the binary tree.
     */
    public function isDescendantOf(int $candidateDescendantId, int $ancestorId): bool
    {
        if ($candidateDescendantId === $ancestorId) {
            return true;
        }

        return DB::table('member_tree_nodes')
            ->where('ancestor_id', $ancestorId)
            ->where('descendant_id', $candidateDescendantId)
            ->where('depth', '>', 0)
            ->exists();
    }

    /**
     * Check if a specific slot (orange or blue) is currently vacant under parent.
     */
    public function isSlotAvailable(int $parentId, string $position): bool
    {
        if (! in_array($position, ['orange', 'blue'], true)) {
            return false;
        }

        return ! DB::table('direct_selling_members')
            ->where('placement_parent_id', $parentId)
            ->where('placement_position', $position)
            ->exists();
    }

    /**
     * Populate closure table hierarchy nodes for a newly registered member.
     *
     * Correct Group Leg Logic:
     * - Self node has depth 0 and group_leg 'self'.
     * - Immediate parent has depth 1 and group_leg = placement_position ('orange' or 'blue').
     * - All higher ancestors inherit the exact branch leg ('orange' or 'blue') under which
     *   the placement parent was situated relative to that ancestor!
     */
    public function populateTreeNodes(DirectSellingMember|int $member, ?int $placementParentId, ?string $placementPosition): void
    {
        $memberId = $member instanceof DirectSellingMember ? $member->id : (int) $member;

        // 1. Insert self record
        DB::table('member_tree_nodes')->insert([
            'ancestor_id' => $memberId,
            'descendant_id' => $memberId,
            'depth' => 0,
            'group_leg' => 'self',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $placementParentId || ! $placementPosition) {
            // Root top node has no upline
            return;
        }

        // 2. Insert immediate placement parent record
        DB::table('member_tree_nodes')->insert([
            'ancestor_id' => $placementParentId,
            'descendant_id' => $memberId,
            'depth' => 1,
            'group_leg' => $placementPosition,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. For all higher ancestors of the placement parent:
        // Copy the ancestor's existing branch group_leg that parent holds relative to that ancestor,
        // incrementing depth by 1.
        $higherAncestors = DB::table('member_tree_nodes')
            ->where('descendant_id', $placementParentId)
            ->where('depth', '>', 0)
            ->get(['ancestor_id', 'depth', 'group_leg']);

        $bulkRows = [];
        foreach ($higherAncestors as $ancestor) {
            $bulkRows[] = [
                'ancestor_id' => $ancestor->ancestor_id,
                'descendant_id' => $memberId,
                'depth' => $ancestor->depth + 1,
                'group_leg' => $ancestor->group_leg, // Crucial: Inherits ancestor's original Orange/Blue branch!
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($bulkRows)) {
            DB::table('member_tree_nodes')->insert($bulkRows);
        }
    }

    /**
     * Compute verified real-time team statistics for a member from DB relationships.
     */
    public function getTeamStatistics(int $memberId): array
    {
        // Direct referrals (Personally sponsored)
        $directCount = DB::table('direct_selling_members')
            ->where('sponsor_id', $memberId)
            ->count();

        // Orange Team query
        $orangeDescendantIds = DB::table('member_tree_nodes')
            ->where('ancestor_id', $memberId)
            ->where('group_leg', 'orange')
            ->where('depth', '>', 0)
            ->pluck('descendant_id');

        $totalOrange = $orangeDescendantIds->count();
        $activeOrange = 0;
        $blockedOrange = 0;
        if ($totalOrange > 0) {
            $activeOrange = DB::table('direct_selling_members')
                ->whereIn('id', $orangeDescendantIds)
                ->where('membership_status', 'active')
                ->count();

            $blockedOrange = DB::table('direct_selling_members')
                ->whereIn('id', $orangeDescendantIds)
                ->whereIn('membership_status', ['suspended', 'terminated'])
                ->count();
        }

        // Blue Team query
        $blueDescendantIds = DB::table('member_tree_nodes')
            ->where('ancestor_id', $memberId)
            ->where('group_leg', 'blue')
            ->where('depth', '>', 0)
            ->pluck('descendant_id');

        $totalBlue = $blueDescendantIds->count();
        $activeBlue = 0;
        $blockedBlue = 0;
        if ($totalBlue > 0) {
            $activeBlue = DB::table('direct_selling_members')
                ->whereIn('id', $blueDescendantIds)
                ->where('membership_status', 'active')
                ->count();

            $blockedBlue = DB::table('direct_selling_members')
                ->whereIn('id', $blueDescendantIds)
                ->whereIn('membership_status', ['suspended', 'terminated'])
                ->count();
        }

        return [
            'direct_referrals' => $directCount,
            'direct_referrals_count' => $directCount,
            'total_orange' => $totalOrange,
            'orange_team_count' => $totalOrange,
            'active_orange' => $activeOrange,
            'blocked_orange' => $blockedOrange,
            'total_blue' => $totalBlue,
            'blue_team_count' => $totalBlue,
            'active_blue' => $activeBlue,
            'blocked_blue' => $blockedBlue,
            'total_team' => $totalOrange + $totalBlue,
            'total_team_count' => $totalOrange + $totalBlue,
            'active_team' => $activeOrange + $activeBlue,
        ];
    }

    /**
     * Build an interactive, recursive hierarchy tree structure starting from root member.
     */
    public function buildTreeHierarchy(int $memberId, int $currentDepth = 1, int $maxDepth = 3): array
    {
        $member = DirectSellingMember::with(['user', 'placementChildren.user'])
            ->find($memberId);

        if (! $member) {
            return [];
        }

        $orangeChild = $member->placementChildren->firstWhere('placement_position', 'orange');
        $blueChild = $member->placementChildren->firstWhere('placement_position', 'blue');

        $counts = $this->getTeamStatistics($member->id);

        $node = [
            'id' => $member->id,
            'member_id' => $member->member_id,
            'tfc_member_id' => $member->member_id,
            'name' => $member->display_name,
            'sponsor_id' => $member->sponsor_id,
            'position' => $member->placement_position,
            'status' => $member->membership_status,
            'activation' => $member->activation_status,
            'kyc_status' => $member->kyc_status,
            'registered_at' => $member->registration_date?->format('d M Y'),
            'orange_count' => $counts['total_orange'],
            'blue_count' => $counts['total_blue'],
            'orange_child' => null,
            'blue_child' => null,
            'children' => [],
        ];

        if ($currentDepth < $maxDepth) {
            if ($orangeChild) {
                $node['orange_child'] = $this->buildTreeHierarchy($orangeChild->id, $currentDepth + 1, $maxDepth);
                $node['children']['orange'] = $node['orange_child'];
            }
            if ($blueChild) {
                $node['blue_child'] = $this->buildTreeHierarchy($blueChild->id, $currentDepth + 1, $maxDepth);
                $node['children']['blue'] = $node['blue_child'];
            }
        }

        return $node;
    }
}
