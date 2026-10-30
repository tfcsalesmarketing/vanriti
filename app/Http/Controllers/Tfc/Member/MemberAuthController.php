<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\Tfc\DirectSellingMember;
use App\Models\User;
use App\Services\Tfc\TfcGenealogyService;
use App\Services\Tfc\TfcMemberIdService;
use App\Services\Tfc\TfcNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MemberAuthController extends Controller
{
    protected TfcMemberIdService $idService;
    protected TfcGenealogyService $genealogyService;
    protected TfcNotificationService $notificationService;

    public function __construct(
        TfcMemberIdService $idService,
        TfcGenealogyService $genealogyService,
        TfcNotificationService $notificationService
    ) {
        $this->idService = $idService;
        $this->genealogyService = $genealogyService;
        $this->notificationService = $notificationService;
    }

    /**
     * Show registration form with optional referral pre-fill.
     */
    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isTfcMember()) {
            return redirect()->route('tfc.preview.member.dashboard');
        }

        $ref = trim((string) ($request->query('ref') ?: $request->query('sponsor', '')));
        $prefilledSponsor = null;

        if ($ref !== '') {
            $prefilledSponsor = DirectSellingMember::where('member_id', $ref)
                ->where('membership_status', 'active')
                ->first();
        }

        return view('tfc.member.auth.register', [
            'prefilledSponsor' => $prefilledSponsor,
            'refCode' => $ref,
        ]);
    }

    /**
     * AJAX endpoint to verify Sponsor ID.
     */
    public function verifySponsorAjax(Request $request): JsonResponse
    {
        $sponsorId = strtoupper(trim((string) $request->input('sponsor_id', '')));

        if ($sponsorId === '') {
            return response()->json(['valid' => false, 'message' => 'Please enter a Sponsor ID.'], 200);
        }

        $sponsor = DirectSellingMember::whereRaw('UPPER(member_id) = ?', [$sponsorId])
            ->where('membership_status', 'active')
            ->first();

        if (! $sponsor) {
            return response()->json([
                'valid' => false,
                'message' => "Invalid Sponsor ID: No active direct seller found with ID \"{$sponsorId}\". Please verify with your sponsor.",
            ], 200);
        }

        return response()->json([
            'valid' => true,
            'sponsor' => [
                'id' => $sponsor->id,
                'tfc_member_id' => $sponsor->tfc_member_id,
                'name' => $sponsor->full_name,
                'mobile_hint' => '•••• ' . substr($sponsor->mobile, -4),
            ],
            'message' => "Sponsor Verified: {$sponsor->full_name} ({$sponsor->tfc_member_id})",
        ], 200);
    }

    /**
     * AJAX endpoint to verify Placement Parent and Slot availability.
     */
    public function verifyPlacementAjax(Request $request): JsonResponse
    {
        $sponsorId = strtoupper(trim((string) $request->input('sponsor_id', '')));
        $parentId = strtoupper(trim((string) $request->input('placement_parent_id', '')));
        $position = strtolower(trim((string) $request->input('position', 'orange')));

        if (! in_array($position, ['orange', 'blue'], true)) {
            return response()->json(['valid' => false, 'message' => 'Invalid group position selected.'], 200);
        }

        if ($parentId === '') {
            return response()->json(['valid' => false, 'message' => 'Please enter a Placement Parent ID.'], 200);
        }

        $parent = DirectSellingMember::whereRaw('UPPER(member_id) = ?', [$parentId])
            ->where('membership_status', 'active')
            ->first();

        if (! $parent) {
            return response()->json([
                'valid' => false,
                'message' => "Invalid Placement Parent ID: No active direct seller found with ID \"{$parentId}\". Please verify.",
            ], 200);
        }

        // Subtree confinement check if sponsor ID is provided
        if ($sponsorId !== '') {
            $sponsor = DirectSellingMember::whereRaw('UPPER(member_id) = ?', [$sponsorId])
                ->where('membership_status', 'active')
                ->first();

            if ($sponsor) {
                if ($parent->id !== $sponsor->id && ! $this->genealogyService->isDescendantOf($parent->id, $sponsor->id)) {
                    return response()->json([
                        'valid' => false,
                        'message' => "Placement Parent {$parent->full_name} ({$parent->tfc_member_id}) is not within Sponsor {$sponsor->full_name}'s team downline.",
                        'parent' => [
                            'id' => $parent->id,
                            'tfc_member_id' => $parent->tfc_member_id,
                            'name' => $parent->full_name,
                        ],
                    ], 200);
                }
            }
        }

        // Check slot availability
        if (! $this->genealogyService->isSlotAvailable($parent->id, $position)) {
            $posName = ucfirst($position);
            return response()->json([
                'valid' => false,
                'message' => "Slot Occupied: The {$posName} position under {$parent->full_name} ({$parent->tfc_member_id}) is already occupied. Please select the other group or another parent.",
                'parent' => [
                    'id' => $parent->id,
                    'tfc_member_id' => $parent->tfc_member_id,
                    'name' => $parent->full_name,
                    'position' => $position,
                ],
            ], 200);
        }

        $posName = ucfirst($position);
        return response()->json([
            'valid' => true,
            'parent' => [
                'id' => $parent->id,
                'tfc_member_id' => $parent->tfc_member_id,
                'name' => $parent->full_name,
                'position' => $position,
            ],
            'message' => "Slot Available: {$parent->full_name} ({$parent->tfc_member_id}) &bull; {$posName} Group Leg",
        ], 200);
    }

    /**
     * Handle new member registration.
     */
    public function register(Request $request): RedirectResponse
    {
        // 1. Sanitize and normalize mobile number (strip non-digits and country code prefixes)
        $rawMobile = (string) $request->input('mobile', '');
        $cleanMobile = preg_replace('/\D/', '', $rawMobile);
        if (strlen($cleanMobile) > 10) {
            if (str_starts_with($cleanMobile, '0091')) {
                $cleanMobile = substr($cleanMobile, 4);
            } elseif (str_starts_with($cleanMobile, '91')) {
                $cleanMobile = substr($cleanMobile, 2);
            } elseif (str_starts_with($cleanMobile, '0')) {
                $cleanMobile = substr($cleanMobile, 1);
            }
        }
        $request->merge(['mobile' => $cleanMobile]);

        // 2. Comprehensive Server-Side Validation
        $validator = Validator::make($request->all(), [
            'sponsor_tfc_id' => ['required', 'string', 'max:50'],
            'placement_parent_tfc_id' => ['required', 'string', 'max:50'],
            'placement_position' => ['required', 'in:orange,blue'],
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'terms' => ['accepted'],
        ], [
            'sponsor_tfc_id.required' => 'Sponsor ID is required.',
            'placement_parent_tfc_id.required' => 'Placement Parent ID is required.',
            'placement_position.required' => 'Please select a placement team group (Orange or Blue).',
            'placement_position.in' => 'Placement group must be either Orange or Blue.',
            'full_name.required' => 'Full legal name is required as per government ID.',
            'full_name.min' => 'Full legal name must be at least 3 characters.',
            'mobile.required' => 'Mobile number is required to receive login credentials via WhatsApp.',
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).',
            'terms.accepted' => 'You must accept the Direct Selling Agreement and Code of Ethics to proceed.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please resolve the highlighted validation errors to complete your registration.');
        }

        $sponsorTfcId = strtoupper(trim((string) $request->input('sponsor_tfc_id')));
        $parentTfcId = strtoupper(trim((string) $request->input('placement_parent_tfc_id')));
        $position = strtolower(trim((string) $request->input('placement_position')));
        $mobile = $cleanMobile;
        $fullName = trim((string) $request->input('full_name'));
        $email = $request->filled('email') ? strtolower(trim((string) $request->input('email'))) : null;

        // Default initial password for all new direct sellers
        $defaultPassword = 'TFC@123456';

        // Check if a direct seller account already exists with this mobile number
        $existingSeller = DirectSellingMember::whereHas('user', function ($q) use ($mobile) {
            $q->where('phone', $mobile);
        })->first();

        if ($existingSeller) {
            return back()
                ->withInput()
                ->withErrors(['mobile' => "Mobile number +91 {$mobile} is already registered as a Direct Seller ({$existingSeller->member_id}). Please sign in."])
                ->with('error', "A Direct Seller account is already registered with mobile number +91 {$mobile} ({$existingSeller->member_id}). Please sign in to your Member Portal.");
        }

        // Check if existing VANRITI client account exists
        $existingCustomer = User::where('phone', $mobile)->first();

        // Validate sponsor & placement parent against database
        $sponsor = DirectSellingMember::whereRaw('UPPER(member_id) = ?', [$sponsorTfcId])
            ->where('membership_status', 'active')
            ->first();
        if (! $sponsor) {
            return back()
                ->withInput()
                ->withErrors(['sponsor_tfc_id' => "Invalid Sponsor ID: No active direct seller found with ID \"{$sponsorTfcId}\". Please verify with your sponsor."])
                ->with('error', "The selected Sponsor ID \"{$sponsorTfcId}\" is invalid or inactive.");
        }

        $parent = DirectSellingMember::whereRaw('UPPER(member_id) = ?', [$parentTfcId])
            ->where('membership_status', 'active')
            ->first();
        if (! $parent) {
            return back()
                ->withInput()
                ->withErrors(['placement_parent_tfc_id' => "Invalid Placement Parent ID: No active direct seller found with ID \"{$parentTfcId}\". Please verify."])
                ->with('error', "The selected Placement Parent ID \"{$parentTfcId}\" is invalid or inactive.");
        }

        if ($parent->id !== $sponsor->id && ! $this->genealogyService->isDescendantOf($parent->id, $sponsor->id)) {
            return back()
                ->withInput()
                ->withErrors(['placement_parent_tfc_id' => "Placement Parent {$parent->full_name} ({$parent->tfc_member_id}) is not within Sponsor {$sponsor->full_name}'s team downline."])
                ->with('error', "Placement Parent is not within the Sponsor's downline team.");
        }

        // Check slot availability before starting transaction
        if (! $this->genealogyService->isSlotAvailable($parent->id, $position)) {
            $posName = ucfirst($position);
            return back()
                ->withInput()
                ->withErrors(['placement_position' => "The {$posName} position under {$parent->full_name} ({$parent->tfc_member_id}) is already occupied."])
                ->with('error', "The {$posName} group slot under {$parent->full_name} ({$parent->tfc_member_id}) is already occupied. Please select the other group or another placement parent.");
        }

        // Atomic transaction with row locking on placement parent
        try {
            $newMember = DB::transaction(function () use ($parent, $sponsor, $position, $fullName, $email, $mobile, $defaultPassword, $existingCustomer, $request) {
                // Lock placement parent row to prevent race conditions
                DirectSellingMember::where('id', $parent->id)->lockForUpdate()->first();

                // Double check slot occupancy inside locked transaction
                if (! $this->genealogyService->isSlotAvailable($parent->id, $position)) {
                    throw new \RuntimeException("The {$position} placement slot under {$parent->tfc_member_id} was just taken by another registration. Please select another placement parent or position.");
                }

                // 1. Resolve User account (reuse existing VANRITI customer or create new)
                if ($existingCustomer) {
                    $user = $existingCustomer;
                    $updateData = [
                        'name' => $fullName,
                        'phone' => $mobile,
                        'password' => Hash::make($defaultPassword),
                        'status' => 'active',
                    ];
                    if ($email) {
                        $updateData['email'] = $email;
                    }
                    $user->update($updateData);
                } else {
                    $user = User::create([
                        'name' => $fullName,
                        'email' => $email,
                        'phone' => $mobile,
                        'password' => Hash::make($defaultPassword),
                        'status' => 'active',
                    ]);
                }

                // 2. Generate sequential Member ID
                $tfcMemberId = $this->idService->generateNextMemberId();

                // 3. Create Direct Selling Member with mobile_verification_status = unverified
                $member = DirectSellingMember::create([
                    'user_id' => $user->id,
                    'member_id' => $tfcMemberId,
                    'sponsor_id' => $sponsor->id,
                    'placement_parent_id' => $parent->id,
                    'placement_position' => $position,
                    'membership_status' => 'active',
                    'mobile_verification_status' => 'unverified',
                    'registration_date' => now()->toDateString(),
                    'registered_ip' => $request->ip(),
                ]);

                // 4. Populate closure table nodes
                $this->genealogyService->populateTreeNodes($member->id, $parent->id, $position);

                return $member;
            });

            // Generate encrypted token for mobile number verification
            $token = $this->notificationService->generateEncryptedVerificationToken($newMember->tfc_member_id, $newMember->mobile);

            // Dispatch welcome notification via WhatsApp (includes profile verification link)
            $this->notificationService->sendWelcomeNotification($newMember, $defaultPassword);

            // Dispatch 6-digit WhatsApp OTP code immediately
            $this->notificationService->sendMobileVerificationOtp($newMember->mobile, $newMember->full_name, $newMember->tfc_member_id);

            // Redirect to mobile verification page with encrypted token
            $verifyUrl = function_exists('tfc_route')
                ? tfc_route('member.verify-mobile', ['token' => $token])
                : route('tfc.preview.member.verify-mobile', ['token' => $token]);

            return redirect()->to($verifyUrl)
                ->with('success', "🎉 Welcome to TFC Sales & Marketing, {$newMember->full_name}! Your Member ID is {$newMember->tfc_member_id}. A verification link has also been sent to your WhatsApp. Please enter the OTP sent to +91 {$newMember->mobile} to set your password.");

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'A concurrency conflict occurred for this placement slot. Please re-verify placement position.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Live validation for Mobile Number on Registration
     */
    public function checkMobileAjax(Request $request): JsonResponse
    {
        $raw = (string) $request->input('mobile', '');
        $mobile = preg_replace('/\D/', '', $raw);
        if (strlen($mobile) > 10) {
            if (str_starts_with($mobile, '0091')) {
                $mobile = substr($mobile, 4);
            } elseif (str_starts_with($mobile, '91')) {
                $mobile = substr($mobile, 2);
            } elseif (str_starts_with($mobile, '0')) {
                $mobile = substr($mobile, 1);
            }
        }

        if (strlen($mobile) !== 10 || ! in_array($mobile[0], ['6', '7', '8', '9'], true)) {
            return response()->json([
                'valid' => false,
                'status' => 'invalid_format',
                'message' => 'Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9 (e.g. 9876543210).',
                'mobile' => $mobile,
            ], 200);
        }

        // Check if already registered as a direct seller
        $seller = DirectSellingMember::whereHas('user', function ($q) use ($mobile) {
            $q->where('phone', $mobile);
        })->first();

        if ($seller) {
            return response()->json([
                'valid' => false,
                'status' => 'already_seller',
                'message' => "Mobile number +91 {$mobile} is already registered as a Direct Seller ({$seller->member_id}). Please sign in.",
                'member_id' => $seller->member_id,
            ], 200);
        }

        // Check if existing VANRITI client
        $existingCustomer = User::where('phone', $mobile)->first();
        if ($existingCustomer) {
            return response()->json([
                'valid' => true,
                'status' => 'existing_client',
                'message' => "Existing VANRITI client found ({$existingCustomer->name}). Your account will be upgraded to Direct Seller.",
                'name' => $existingCustomer->name,
                'mobile' => $mobile,
            ], 200);
        }

        return response()->json([
            'valid' => true,
            'status' => 'available',
            'message' => "Mobile number +91 {$mobile} is available for new registration.",
            'mobile' => $mobile,
        ], 200);
    }

    /**
     * AJAX endpoint: Live validation for Email on Registration
     */
    public function checkEmailAjax(Request $request): JsonResponse
    {
        $email = strtolower(trim((string) $request->input('email', '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'valid' => false,
                'status' => 'invalid_format',
                'message' => 'Please enter a valid email address.',
            ], 422);
        }

        // Check if already registered as a direct seller
        $seller = DirectSellingMember::whereHas('user', function ($q) use ($email) {
            $q->where('email', $email);
        })->first();

        if ($seller) {
            return response()->json([
                'valid' => false,
                'status' => 'already_seller',
                'message' => "This email is already registered as a Direct Seller ({$seller->member_id}). Please sign in.",
            ], 422);
        }

        $existingCustomer = User::where('email', $email)->first();
        if ($existingCustomer) {
            return response()->json([
                'valid' => true,
                'status' => 'existing_client',
                'message' => "VANRITI client found ({$existingCustomer->name}). Account will be upgraded.",
                'name' => $existingCustomer->name,
            ]);
        }

        return response()->json([
            'valid' => true,
            'status' => 'available',
            'message' => 'Email address available.',
        ]);
    }

    /**
     * AJAX endpoint: Live validation on Login page (validates Member ID / Phone / Email)
     */
    public function checkLoginAjax(Request $request): JsonResponse
    {
        $login = trim((string) $request->input('login', ''));

        if ($login === '') {
            return response()->json(['valid' => false, 'message' => 'Please enter Member ID, Email, or Mobile.'], 422);
        }

        $member = DirectSellingMember::where('member_id', $login)->first();
        if (! $member) {
            $user = User::where('phone', $login)->orWhere('email', $login)->first();
            $member = $user?->directSellingMember;
        }

        if (! $member) {
            return response()->json([
                'valid' => false,
                'message' => 'No active Direct Seller account found with this ID / Mobile.',
            ], 404);
        }

        if ($member->status === 'suspended') {
            return response()->json([
                'valid' => false,
                'message' => 'Account is suspended. Please contact support.',
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'member_id' => $member->member_id,
            'name' => $member->full_name,
            'initial' => strtoupper(substr($member->full_name, 0, 1)),
        ]);
    }

    /**
     * Show member login page.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isTfcMember()) {
            return redirect()->route('tfc.preview.member.dashboard');
        }

        return view('tfc.member.auth.login');
    }

    /**
     * Authenticate member login.
     */
    public function login(Request $request): RedirectResponse
    {
        $throttleKey = 'tfc_member_login|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Too many failed login attempts. Please try again in {$seconds} seconds.");
        }

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $remember = (bool) $request->boolean('remember');

        // Resolve member by TFC Member ID, Email, or Mobile
        $member = DirectSellingMember::where('member_id', $login)->first();
        if (! $member) {
            $foundUser = User::where('email', $login)->orWhere('phone', $login)->first();
            $member = $foundUser?->directSellingMember;
        }

        if (! $member || ! $member->user) {
            RateLimiter::hit($throttleKey, 300);
            return back()->withInput($request->only('login'))->with('error', 'Invalid Member ID or credentials.');
        }

        if ($member->status === 'suspended') {
            return back()->with('error', 'Your TFC Member account has been suspended. Please contact administrative support.');
        }

        if ($member->status === 'terminated') {
            return back()->with('error', 'Your TFC Member account is terminated.');
        }

        if (! Hash::check($password, $member->user->password)) {
            RateLimiter::hit($throttleKey, 300);
            return back()->withInput($request->only('login'))->with('error', 'Invalid Member ID or credentials.');
        }

        RateLimiter::clear($throttleKey);

        // First-time or Unverified Member Interception:
        // If mobile is not verified yet, redirect to verify-mobile with encrypted token
        if ($member->mobile_verification_status !== 'verified') {
            $token = $this->notificationService->generateEncryptedVerificationToken($member->tfc_member_id, $member->mobile);
            $this->notificationService->sendMobileVerificationOtp($member->mobile, $member->full_name, $member->tfc_member_id);

            $verifyUrl = function_exists('tfc_route')
                ? tfc_route('member.verify-mobile', ['token' => $token])
                : route('tfc.preview.member.verify-mobile', ['token' => $token]);

            return redirect()->to($verifyUrl)
                ->with('warning', 'Please verify your mobile number and set your personal password before accessing the Member Portal.');
        }

        Auth::login($member->user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('tfc.preview.member.dashboard'))
            ->with('success', "Welcome back, {$member->full_name}!");
    }

    /**
     * Show mobile verification and OTP entry screen.
     */
    public function showVerifyMobile(Request $request): View|RedirectResponse
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Invalid or missing verification link. Please sign in or register.');
        }

        $payload = $this->notificationService->decryptVerificationToken($token);
        if (! $payload) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'The verification link has expired or is invalid. Please request a new one.');
        }

        $member = DirectSellingMember::where('member_id', $payload['member_id'])->first();
        if (! $member) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Member account not found.');
        }

        if ($member->mobile_verification_status === 'verified') {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('info', "Your mobile number +91 {$member->mobile} is already verified. Please sign in.")
                ->with('prefill_login', $member->tfc_member_id);
        }

        // Auto-send OTP if no active OTP was generated recently
        $cleanMobile = preg_replace('/\D/', '', $member->mobile);
        $activeOtp = OtpCode::active($cleanMobile, 'tfc_mobile_verify')->first();
        if (! $activeOtp) {
            $this->notificationService->sendMobileVerificationOtp($member->mobile, $member->full_name, $member->tfc_member_id);
        }

        $maskedMobile = '+91 ' . substr($member->mobile, 0, 2) . '••••' . substr($member->mobile, -4);

        return view('tfc.member.auth.verify-mobile', [
            'member' => $member,
            'token' => $token,
            'maskedMobile' => $maskedMobile,
            'mobile' => $member->mobile,
        ]);
    }

    /**
     * Resend verification OTP code via WhatsApp.
     */
    public function resendVerifyMobileOtp(Request $request): JsonResponse|RedirectResponse
    {
        $token = trim((string) $request->input('token', $request->query('token', '')));
        $payload = $this->notificationService->decryptVerificationToken($token);

        if (! $payload) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired session. Please refresh the page.'], 422);
            }
            return back()->with('error', 'Invalid verification session. Please reload the page.');
        }

        $member = DirectSellingMember::where('member_id', $payload['member_id'])->first();
        if (! $member) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
            }
            return back()->with('error', 'Member account not found.');
        }

        $res = $this->notificationService->sendMobileVerificationOtp($member->mobile, $member->full_name, $member->tfc_member_id);

        if ($request->wantsJson()) {
            return response()->json($res, $res['success'] ? 200 : 422);
        }

        if ($res['success']) {
            return back()->with('success', 'A new verification code has been dispatched to your WhatsApp.');
        }

        return back()->with('error', $res['message'] ?? 'Could not send verification code.');
    }

    /**
     * Verify submitted OTP code.
     */
    public function verifyMobileOtpSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'otp' => ['required', 'string'],
        ], [
            'otp.required' => 'Please enter the 6-digit verification code.',
        ]);

        $token = trim((string) $request->input('token'));
        $payload = $this->notificationService->decryptVerificationToken($token);

        if (! $payload) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Your verification session expired. Please log in or re-open the link.');
        }

        $member = DirectSellingMember::where('member_id', $payload['member_id'])->first();
        if (! $member) {
            return back()->with('error', 'Member account not found.');
        }

        $cleanMobile = preg_replace('/\D/', '', $member->mobile);
        $enteredCode = preg_replace('/\D/', '', (string) $request->input('otp'));

        if (strlen($enteredCode) !== 6) {
            return back()->withInput()->with('error', 'Please enter a complete 6-digit verification code.');
        }

        $otpRecord = OtpCode::active($cleanMobile, 'tfc_mobile_verify')->first();

        if (! $otpRecord) {
            return back()->withInput()->with('error', 'The OTP code has expired or was not requested. Please tap "Resend Code" to receive a new one.');
        }

        if ($otpRecord->attempts >= 5) {
            $otpRecord->update(['used_at' => now()]);
            return back()->withInput()->with('error', 'Too many incorrect attempts. Please tap "Resend Code" to receive a new code.');
        }

        if (! Hash::check($enteredCode, $otpRecord->hashed_code)) {
            $otpRecord->increment('attempts');
            $remaining = max(0, 5 - $otpRecord->attempts);
            return back()->withInput()->with('error', "Incorrect verification code. {$remaining} attempt(s) remaining.");
        }

        // Mark OTP as used
        $otpRecord->update(['used_at' => now()]);

        // Generate verified token for step 2: Set Password
        $verifiedToken = Crypt::encryptString(json_encode([
            'member_id' => $member->tfc_member_id,
            'mobile' => $cleanMobile,
            'verified_at' => now()->timestamp,
        ]));

        $setPasswordUrl = function_exists('tfc_route')
            ? tfc_route('member.set-password', ['token' => $verifiedToken])
            : route('tfc.preview.member.set-password', ['token' => $verifiedToken]);

        return redirect()->to($setPasswordUrl)
            ->with('success', 'Mobile number verified successfully! Please set your new portal password.');
    }

    /**
     * Show Set New Password page after OTP verification.
     */
    public function showSetPassword(Request $request): View|RedirectResponse
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Verification session token missing.');
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Invalid password setup link.');
        }

        if (empty($payload['member_id']) || empty($payload['verified_at'])) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Invalid verification session.');
        }

        // Expire setup token after 30 minutes
        if (now()->timestamp - (int) $payload['verified_at'] > 1800) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Your password setup session has expired. Please verify your mobile number again.');
        }

        $member = DirectSellingMember::where('member_id', $payload['member_id'])->first();
        if (! $member) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Member account not found.');
        }

        return view('tfc.member.auth.set-password', [
            'member' => $member,
            'token' => $token,
        ]);
    }

    /**
     * Save new password and mark mobile as verified.
     */
    public function saveSetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $token = trim((string) $request->input('token'));

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Invalid or expired setup token.');
        }

        if (empty($payload['member_id'])) {
            return redirect()->to(function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login'))
                ->with('error', 'Invalid verification session.');
        }

        $member = DirectSellingMember::where('member_id', $payload['member_id'])->first();
        if (! $member || ! $member->user) {
            return back()->with('error', 'Member account not found.');
        }

        // Update password and phone verification status
        $member->user->update([
            'password' => Hash::make($request->input('password')),
            'phone_verified_at' => now(),
        ]);

        $member->update([
            'mobile_verification_status' => 'verified',
        ]);

        $loginUrl = function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login');

        return redirect()->to($loginUrl)
            ->with('success', "🎉 Password set successfully! You can now log in using your Member ID ({$member->tfc_member_id}) and your new password.")
            ->with('prefill_login', $member->tfc_member_id);
    }

    /**
     * Log out member from portal.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tfc.preview.member.login')
            ->with('info', 'You have been logged out of the TFC Member Portal.');
    }

    /**
     * Show expiring signed password setup page.
     */
    public function showPasswordSetup(Request $request): View|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('tfc.preview.member.login')
                ->with('error', 'This password setup link is invalid or has expired. Please request a new one.');
        }

        $memberId = (string) $request->query('member_id');
        $email = (string) $request->query('email');

        $member = DirectSellingMember::where('member_id', $memberId)->first();
        if (! $member) {
            return redirect()->route('tfc.preview.member.login')->with('error', 'Member account not found.');
        }

        return view('tfc.member.auth.password-setup', [
            'member' => $member,
            'email' => $email,
        ]);
    }

    /**
     * Process password setup from signed URL.
     */
    public function savePasswordSetup(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('tfc.preview.member.login')
                ->with('error', 'This password setup link has expired. Please request a new one.');
        }

        $request->validate([
            'member_id' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $member = DirectSellingMember::where('member_id', $request->input('member_id'))->firstOrFail();
        $user = $member->user;

        $user->password = Hash::make($request->input('password'));
        $user->save();

        Auth::login($user);

        return redirect()->route('tfc.preview.member.dashboard')
            ->with('success', 'Your password has been successfully set up. Welcome to your TFC Member Portal!');
    }
}
