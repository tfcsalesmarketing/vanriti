<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use App\Models\Tfc\MemberKyc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KycDocumentController extends Controller
{
    /**
     * Securely stream KYC document to authorized member or admin.
     */
    public function view(int $id, string $type, Request $request): BinaryFileResponse
    {
        $kyc = MemberKyc::findOrFail($id);

        $isAdmin = Auth::guard('admin')->check();
        $isOwner = Auth::guard('web')->check() && Auth::user()->directSellingMember?->id === $kyc->direct_selling_member_id;

        if (! $isAdmin && ! $isOwner) {
            abort(403, 'Unauthorized access to confidential document.');
        }

        $path = match ($type) {
            'front' => $kyc->document_front_path,
            'back' => $kyc->document_back_path,
            'pan' => $kyc->pan_document_path,
            default => null,
        };

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404, 'Document file not found.');
        }

        $fullPath = Storage::disk('local')->path($path);

        return response()->file($fullPath, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
