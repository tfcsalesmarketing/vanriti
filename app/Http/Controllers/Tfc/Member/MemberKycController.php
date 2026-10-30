<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use App\Models\Tfc\MemberKyc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MemberKycController extends Controller
{
    /**
     * Show member KYC status and submission form.
     */
    public function index(): View
    {
        $member = Auth::user()->directSellingMember;
        $kyc = $member->kyc;

        return view('tfc.member.kyc.index', [
            'member' => $member,
            'kyc' => $kyc,
        ]);
    }

    /**
     * Submit KYC documents.
     */
    public function store(Request $request): RedirectResponse
    {
        $member = Auth::user()->directSellingMember;

        if ($member->kyc && $member->kyc->status === 'verified') {
            return back()->with('info', 'Your KYC is already verified.');
        }

        $request->validate([
            'document_type' => ['required', 'in:aadhaar,pan,voter_id,passport'],
            'document_number' => ['required', 'string', 'max:50'],
            'document_front' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
            'document_back' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
            'pan_number' => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'pan_document' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:4096'],
        ], [
            'pan_number.regex' => 'Please provide a valid 10-character PAN number (e.g. ABCDE1234F).',
            'document_front.max' => 'Document file size must not exceed 4MB.',
            'document_back.max' => 'Document file size must not exceed 4MB.',
        ]);

        $rawDocNumber = trim((string) $request->input('document_number'));
        $docType = (string) $request->input('document_type');

        // Mask document number for preview display
        $masked = '•••• ' . substr($rawDocNumber, -4);
        if ($docType === 'aadhaar') {
            $digitsOnly = preg_replace('/\D/', '', $rawDocNumber);
            $masked = '•••• •••• ' . substr($digitsOnly, -4);
        }

        // Store files securely in private local disk (storage/app/private/tfc/kyc/)
        $frontPath = $request->file('document_front')->store('tfc/kyc', 'local');
        $backPath = $request->file('document_back') ? $request->file('document_back')->store('tfc/kyc', 'local') : null;
        $panDocPath = $request->file('pan_document') ? $request->file('pan_document')->store('tfc/kyc', 'local') : null;

        // Create or update member KYC submission
        MemberKyc::updateOrCreate(
            ['direct_selling_member_id' => $member->id],
            [
                'document_type' => $docType,
                'document_number' => $rawDocNumber, // Encrypted at rest
                'masked_document_number' => $masked,
                'document_front_path' => $frontPath,
                'document_back_path' => $backPath,
                'pan_number' => $request->input('pan_number') ? strtoupper(trim((string) $request->input('pan_number'))) : null, // Encrypted
                'pan_document_path' => $panDocPath,
                'status' => 'pending',
                'rejection_reason' => null,
                'submitted_at' => now(),
            ]
        );

        $member->update(['kyc_status' => 'pending']);

        return back()->with('success', 'Your KYC documents have been uploaded successfully and submitted for administrative review.');
    }
}
