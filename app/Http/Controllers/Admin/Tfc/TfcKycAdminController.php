<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\MemberKyc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcKycAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', 'pending'));
        $search = trim((string) $request->query('q', ''));

        $query = MemberKyc::with(['directSellingMember'])
            ->latest('submitted_at');

        if ($status !== '' && in_array($status, ['pending', 'verified', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->whereHas('directSellingMember', function ($q) use ($search) {
                $q->where('member_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $submissions = $query->paginate(20)->withQueryString();

        return view('admin.tfc.kyc.index', [
            'submissions' => $submissions,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(int $id): View
    {
        $kyc = MemberKyc::with(['directSellingMember'])->findOrFail($id);

        return view('admin.tfc.kyc.show', [
            'kyc' => $kyc,
            'member' => $kyc->directSellingMember,
        ]);
    }

    public function approve(int $id): RedirectResponse
    {
        $kyc = MemberKyc::with(['directSellingMember'])->findOrFail($id);

        $kyc->update([
            'status' => 'verified',
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        $kyc->directSellingMember->update([
            'kyc_status' => 'verified',
        ]);

        return back()->with('success', "KYC submission for {$kyc->directSellingMember->tfc_member_id} approved successfully.");
    }

    public function reject(int $id, Request $request): RedirectResponse
    {
        $kyc = MemberKyc::with(['directSellingMember'])->findOrFail($id);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $reason = trim((string) $request->input('rejection_reason'));

        $kyc->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $kyc->directSellingMember->update([
            'kyc_status' => 'rejected',
        ]);

        return back()->with('warning', "KYC submission for {$kyc->directSellingMember->tfc_member_id} rejected. Reason recorded.");
    }
}
