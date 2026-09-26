<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Consent\ConsentService;
use App\Services\CartService;
use App\Services\WhatsAppOtpService;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OtpController extends Controller
{
    protected array $purposes = ['login', 'register', 'reset_password'];

    public function __construct(
        protected WhatsAppOtpService $whatsapp,
        protected CartService $cartService,
        protected WishlistService $wishlistService,
        protected ConsentService $consentService,
    ) {}

    // ── Send OTP ───────────────────────────────────────────────────────────────
    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'purpose' => ['required', 'string', 'in:'.implode(',', $this->purposes)],
        ], [
            'phone.required' => 'Please enter your mobile number.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number.',
            'purpose.in' => 'Invalid verification purpose.',
        ]);

        $phone = Str::of($data['phone'])->replace([' ', '-'], '')->trim()->toString();

        // Per-phone daily budget, independent of the IP throttle: a single
        // number must never be flooded with OTP deliveries. Bound both valid
        // and lookup-only sends so enumeration attempts cost rate-limit slots.
        $phoneKey = 'otp-send-phone:'.$phone;
        $dailyLimit = max(3, (int) setting('whatsapp_otp_daily_limit', 10));
        if (RateLimiter::tooManyAttempts($phoneKey, $dailyLimit)) {
            $throttled = 'Too many OTP requests for this number. Please try again later.';

            if ($request->wantsJson()) {
                return response()->json(['result' => '0', 'message' => $throttled], 429);
            }

            return back()->with('error', $throttled)->withInput();
        }

        RateLimiter::hit($phoneKey, 86400);

        // For the login flow an unknown number must be answered exactly like a
        // known one (same status code, same message shape) so the endpoint can
        // never be used to enumerate registered numbers. No code is dispatched
        // for an account that does not exist.
        if ($data['purpose'] === 'login') {
            $loginUser = \App\Models\User::query()->where('phone', $phone)->first();

            if (! $loginUser) {
                $cover = 'If that number is registered, we will send a code to your WhatsApp.';

                if ($request->wantsJson()) {
                    return response()->json([
                        'result' => '1',
                        'message' => $cover,
                        'data' => ['phone' => $phone],
                    ], 202);
                }

                return back()->with('success', $cover)->withInput();
            }
        }

        // Cooldown: reuse the in-flight OTP instead of re-hitting WhatsApp.
        $remaining = $this->whatsapp->cooldownRemaining($phone, $data['purpose']);

        if ($remaining > 0) {
            if ($request->wantsJson()) {
                return response()->json(['result' => '0', 'message' => 'Please wait '.$remaining.' seconds before requesting a new code.'], 429);
            }

            return back()->with('error', 'Please wait '.$remaining.' seconds before requesting a new code.')->withInput();
        }

        $result = $this->whatsapp->sendOtp($phone, $data['purpose']);

        if (($result['result'] ?? '0') === '1') {
            session()->put('otp_pending_phone', $phone);

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return back()->with('success', 'OTP sent to your WhatsApp.');
        }

        if ($request->wantsJson()) {
            return response()->json($result, 422);
        }

        return back()->with('error', $result['message'] ?? 'OTP could not be sent.')->withInput();
    }

    // ── Verify OTP ─────────────────────────────────────────────────────────────
    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:15'],
            'code' => ['required', 'string', 'digits:6'],
            'purpose' => ['required', 'string', 'in:'.implode(',', $this->purposes)],
        ], [
            'phone.required' => 'Please enter your mobile number.',
            'code.required' => 'Please enter the code sent to your WhatsApp.',
            'code.digits' => 'The code must be exactly 6 digits.',
            'purpose.in' => 'Invalid verification purpose.',
        ]);

        $phone = Str::of($data['phone'])->replace([' ', '-'], '')->trim()->toString();

        $result = $this->whatsapp->verifyOtp($phone, $data['code'], $data['purpose']);

        if (($result['result'] ?? '0') !== '1') {
            if ($request->wantsJson()) {
                return response()->json($result, 422);
            }

            return back()->with('error', $result['message'] ?? 'The code you entered is invalid.')->withInput();
        }

        // ── login: log the user in (negates the password step) ────────────────
        if ($data['purpose'] === 'login') {
            $user = \App\Models\User::query()->where('phone', $phone)->first();

            if (! $user) {
                return back()->with('error', 'Unable to sign you in. Please try again or register a new account.');
            }

            if ($user->status !== 'active') {
                return back()->with('error', 'Your account has been suspended. Contact support.');
            }

            $user->update([
                'last_login_at' => now(),
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ]);

            auth('web')->login($user);
            session()->regenerate();
            session()->regenerateToken();

            $this->cartService->mergeGuestCartIntoUser($user);
            $this->wishlistService->mergeGuestIntoUser($user);
            $this->consentService->mergeAnonymousIntoUser($user);

            // OTP login regenerates the session, so the existing CSRF token is now
            // stale. Ship a fresh token back so the storefront modal can replay the
            // pending add-to-cart / wishlist POST without hitting a 419 mismatch.
            $result['csrf_token'] = csrf_token();

            if ($request->wantsJson()) {
                // OTP login regenerates the session + CSRF token. Hand back a fresh
                // token so the modal can replay the pending add/wishlist POST without
                // hitting a 419 mismatch.
                $result['csrf_token'] = csrf_token();
                return response()->json($result);
            }

            return redirect()->intended(route('account.dashboard'));
        }

        // ── register: remember the verified phone for the next step ───────────
        if ($data['purpose'] === 'register') {
            session()->put('otp_verified_phone', $phone);
            session()->forget('otp_pending_phone');

            if ($request->wantsJson()) {
                return response()->json($result);
            }

            return back()->with('success', 'Phone verified. You may now complete registration.');
        }

        // ── reset_password: send the user to the password reset form ---------
        $user = \App\Models\User::query()->where('phone', $phone)->first();

        if (! $user) {
            return back()->with('error', 'Unable to reset the password for this number. Please try again.');
        }

        $token = \Illuminate\Support\Facades\Password::broker('users')->createToken($user);

        return redirect()->route('password.reset', ['token' => $token])->withInput(['phone' => $phone]);
    }
}
