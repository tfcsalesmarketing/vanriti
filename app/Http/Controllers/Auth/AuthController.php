<?php

namespace App\Http\Controllers\Auth;

use App\Consent\ConsentService;
use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\CartService;
use App\Services\EmailOtpService;
use App\Services\WhatsAppOtpService;
use App\Services\WishlistService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected WishlistService $wishlistService,
        protected ConsentService $consentService,
        protected WhatsAppOtpService $whatsapp,
        protected EmailOtpService $emailOtp,
    ) {}

    public function showLogin(): View
    {
        return view('storefront.auth.login');
    }

    public function login(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ], $this->messages());

        $identifier = Str::lower(trim($credentials['login']));

        // ── Resolve the account by email OR mobile, whichever was entered ─────
        // A single "Email or mobile" field keeps the form compact while still
        // supporting both identifiers. We normalise the phone (strip spaces,
        // dashes and the leading +91 / 0) before the lookup so "98765 43210",
        // "9876543210" and "919876543210" all resolve to the same account.
        $user = Str::contains($identifier, '@')
            ? User::where('email', $identifier)->first()
            : user_by_phone($credentials['login']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            // Single generic message prevents email-enumeration via error wording.
            if ($request->wantsJson()) {
                return response()->json([
                    'result' => '0',
                    'message' => 'These credentials do not match our records.',
                ], 422);
            }

            return back()->withErrors([
                'login' => 'These credentials do not match our records.',
            ])->onlyInput('login');
        }

        if ($user->status !== 'active') {
            if ($request->wantsJson()) {
                return response()->json([
                    'result' => '0',
                    'message' => 'Your account has been suspended. Contact support.',
                ], 403);
            }

            return back()->withErrors(['login' => 'Your account has been suspended. Contact support.']);
        }

        $this->rememberCanonicalPhone($user, $credentials['login']);

        auth('web')->login($user, $request->boolean('remember'));

        $user->update(['last_login_at' => now()]);
        session()->regenerate();
        session()->regenerateToken();

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->wishlistService->mergeGuestIntoUser($user);
        $this->consentService->mergeAnonymousIntoUser($user);

        if ($request->wantsJson()) {
            return response()->json([
                'result' => '1',
                'message' => 'Logged in successfully.',
                'redirect' => route('account.dashboard'),
                // Login regenerates the session token — hand the fresh token back
                // so the modal can patch the replayed cart/wishlist POST.
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function showRegister(): View
    {
        return view('storefront.auth.register');
    }

    /**
     * Email-claim verification — informational only, no login gate.
     *
     * The signed URL proves the person who opened the welcome mail owns the
     * link (signature → `email_verified_at`). The guard is deliberately
     * informational: an unverified email never blocks an account.
     */
    public function verifyEmail(User $user): RedirectResponse
    {
        if (! empty($user->email) && empty($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route('account.dashboard')->with('success', 'Email verified successfully. Thank you!');
    }

    public function register(Request $request): RedirectResponse
    {
        $canonical = canonical_phone($request->input('phone'));

        if ($canonical !== null) {
            $request->merge(['phone' => $canonical]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:15', 'unique:users,phone', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ], $this->messages());

        $phone = canonical_phone($data['phone']);

        if ($phone === null) {
            return back()->withErrors(['phone' => 'Please enter a valid 10-digit mobile number.'])->withInput();
        }

        // ── WhatsApp OTP gate: registration requires a phone that was verified ──
        // via OTP in this browser session when the feature is enabled. This keeps
        // attackers from registering with numbers they don't control.
        $whatsapp = app(\App\Services\WhatsAppOtpService::class);
        if ($whatsapp->isEnabled()) {
            $verifiedPhone = (string) session('otp_verified_phone', '');

            if (empty($verifiedPhone) || $verifiedPhone !== $phone) {
                return back()
                    ->withErrors(['phone' => 'Please verify your mobile number with the WhatsApp OTP first.'])
                    ->withInput();
            }
        }

        // Prevent duplicate registrations on the same (normalized) phone number
        // even when whitespace or dashes differ (e.g. "98765 43210" vs
        // "9876543210").
        if (user_by_phone($phone)) {
            return back()->withErrors(['phone' => 'This mobile number is already registered.'])->withInput();
        }

        $email = ! empty($data['email'] ?? null)
            ? Str::lower(trim($data['email']))
            : null;

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'password' => $data['password'],
            'status' => 'active',
            'phone_verified_at' => $whatsapp->isEnabled() ? now() : null,
        ]);

        if (! empty($user->email)) {
            try {
                $user->notify(new WelcomeNotification);
            } catch (\Throwable $e) {
                Log::warning('Welcome email could not be delivered for newly registered user.', [
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // WhatsApp welcome (approved single-variable template; the one body
        // variable is the customer's first name). Fire-and-forget — a welcome
        // delivery failure must never block the new account from being created.
        try {
            if ($whatsapp->isEnabled() && ! empty(trim((string) $user->phone))) {
                $firstName = explode(' ', trim((string) $user->name))[0] ?? '';
                $whatsapp->sendWelcome($user->phone, $firstName);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp welcome could not be delivered for newly registered user.', [
                'phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);
        }

        auth('web')->login($user);
        session()->regenerate();
        session()->regenerateToken();

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->wishlistService->mergeGuestIntoUser($user);
        $this->consentService->mergeAnonymousIntoUser($user);

        return redirect()->intended(route('account.dashboard'));
    }

    public function validateFields(Request $request): JsonResponse
    {
        $context = in_array($request->input('context'), ['register', 'forgot', 'reset', 'setpassword'], true)
            ? $request->input('context')
            : 'login';
        $rules = $this->rulesFor(context: $context, request: $request);

        $subset = [];
        foreach ($rules as $field => $rule) {
            if ($request->exists($field)) {
                $subset[$field] = $rule;
            }
        }

        $validator = Validator::make($request->only(array_keys($subset)), $subset, $this->messages());

        if ($validator->fails()) {
            return response()->json(['valid' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        if ($context === 'register' && $request->exists('phone') && user_by_phone($request->input('phone'))) {
            return response()->json([
                'valid' => false,
                'errors' => ['phone' => ['This mobile number is already registered.']],
            ], 422);
        }

        if ($context === 'login' && $request->exists('login') && $request->exists('password')) {
            $login = Str::lower(trim((string) $request->input('login')));

            // Per-credential rate limiter prevents live-validation from being used
            // as a brute-force oracle. When the limit is hit, the request is
            // rejected before any expensive Hash::check runs.
            $throttleKey = 'auth-validate:'.hash('sha256', $login).':'.$request->ip();
            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['password' => ['Too many attempts. Please wait a minute and try again.']],
                ], 429);
            }

            RateLimiter::hit($throttleKey, 60);

            // Resolve the account by email OR mobile, mirroring the login
            // controller's single-field lookup.
            $user = Str::contains($login, '@')
                ? User::where('email', $login)->first()
                : user_by_phone($login);

            // Always report failures under a single generic key + body: the
            // client can never distinguish a missing account from a wrong
            // password, so the endpoint cannot be used to enumerate accounts.
            if (! $user || $user->status !== 'active' || ! Hash::check((string) $request->input('password'), $user->password)) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['password' => ['These credentials do not match our records.']],
                ], 422);
            }
        }

        return response()->json(['valid' => true]);
    }

    protected function rulesFor(string $context, Request $request): array
    {
        if ($context === 'register') {
            $password = ['required', $this->passwordRule()];
            if ($request->exists('password') && $request->exists('password_confirmation')) {
                $password[] = 'confirmed';
            }

            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
                'password' => $password,
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        if ($context === 'forgot') {
            return [
                'identifier' => ['required', 'string', 'max:255'],
            ];
        }

        // The OTP flow's final step: the account was already resolved by the
        // verified code, so only the two password fields are present.
        if ($context === 'setpassword') {
            $password = ['required', $this->passwordRule()];
            if ($request->exists('password') && $request->exists('password_confirmation')) {
                $password[] = 'confirmed';
            }

            return [
                'password' => $password,
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        if ($context === 'reset') {
            $password = ['required', $this->passwordRule()];
            if ($request->exists('password') && $request->exists('password_confirmation')) {
                $password[] = 'confirmed';
            }

            if ($request->exists('phone')) {
                return [
                    'phone' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
                    'password' => $password,
                    'password_confirmation' => ['required', 'same:password'],
                ];
            }

            return [
                'email' => ['required', 'email'],
                'password' => $password,
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ];
    }

    protected function passwordRule(): Rules\Password
    {
        return Rules\Password::min(8)->letters()->numbers()->max(72);
    }

    protected function messages(): array
    {
        return [
            'login.required' => 'Please enter your email or mobile number.',
            'identifier.required' => 'Please enter your email address or mobile number.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
            'password.max' => 'Password must not exceed 72 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password_confirmation.same' => 'The password confirmation does not match.',
            'name.required' => 'Please enter your full name.',
            'name.max' => 'Name must not exceed 255 characters.',
            'phone.required' => 'Please enter your mobile number.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number.',
            'phone.unique' => 'This mobile number is already registered.',
        ];
    }

    public function logout(): RedirectResponse
    {
        $user = auth('web')->user();

        // Hand the cart back to the browser session first, otherwise the items
        // stay attached to the account and the next guest request shows an
        // empty cart.
        if ($user) {
            $this->cartService->releaseUserCartToSession($user);
        }

        auth('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgot(): View
    {
        return view('storefront.auth.forgot-password');
    }

    /**
     * Step 1 — resolve an email address or mobile number and dispatch a 6-digit
     * code. A mobile number is messaged over WhatsApp; an email address gets a
     * code by mail. If WhatsApp is switched off (or unconfigured) the code is
     * sent to the account's email address instead.
     *
     * The response is identical for a known and an unknown identifier, so this
     * step cannot be used to discover which addresses or numbers are
     * registered. The one exception is a genuine delivery failure, which is
     * surfaced because a silently stuck customer is worse than the small
     * amount of account-existence it reveals — and the register flow already
     * discloses duplicate numbers and emails.
     */
    public function sendPasswordOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ], [
            'identifier.required' => 'Please enter your email address or mobile number.',
        ]);

        $raw = trim($data['identifier']);
        $looksLikeEmail = Str::contains($raw, '@');

        if ($looksLikeEmail) {
            $identifier = Str::lower($raw);
            $valid = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
        } else {
            $identifier = canonical_phone($raw) ?? '';
            $valid = $identifier !== '';
        }

        if (! $valid) {
            return back()
                ->withErrors(['identifier' => 'Please enter a valid email address or 10-digit mobile number.'])
                ->withInput();
        }

        $user = $looksLikeEmail
            ? User::query()->where('email', $identifier)->first()
            : user_by_phone($identifier);

        $channel = $looksLikeEmail ? 'email' : 'whatsapp';
        $message = $looksLikeEmail
            ? 'We sent a 6-digit code to your email address.'
            : 'We sent a 6-digit code to your WhatsApp.';

        // WhatsApp cannot reach a phone-only account while the channel is off,
        // so fall back to the inbox when the account has one.
        if ($user && $channel === 'whatsapp' && ! $this->whatsapp->isEnabled() && ! empty($user->email)) {
            $channel = 'email';
            $identifier = Str::lower((string) $user->email);
            $message = 'WhatsApp verification is unavailable right now, so we sent a 6-digit code to your email address instead.';
        }

        if ($user) {
            $sent = $channel === 'email'
                ? $this->emailOtp->sendOtp($identifier, 'reset_password')
                : $this->whatsapp->sendOtp($identifier, 'reset_password');

            if (($sent['result'] ?? '0') !== '1') {
                Log::warning('Password reset code could not be dispatched.', [
                    'channel' => $channel,
                    'error' => $sent['message'] ?? 'unknown',
                ]);

                return back()->with('error', __('We could not send a verification code right now. Please try again in a moment.'));
            }
        }

        $request->session()->put('password_reset', [
            'identifier' => $identifier,
            'channel' => $channel,
        ]);

        return redirect()->route('password.otp.verify')->with('success', $message);
    }

    /** Step 2 — ask for the 6-digit code that was just dispatched. */
    public function showOtpVerify(): View|RedirectResponse
    {
        $pending = $this->pendingReset();

        if ($pending === null) {
            return redirect()->route('password.request');
        }

        return view('storefront.auth.verify-otp', ['channel' => $pending['channel']]);
    }

    /** Step 2 submit — burn the code and unlock the new-password step. */
    public function verifyPasswordOtp(Request $request): RedirectResponse
    {
        $pending = $this->pendingReset();

        if ($pending === null) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Please enter the 6-digit code we sent you.',
            'code.digits' => 'The code must be exactly 6 digits.',
        ]);

        $result = $pending['channel'] === 'whatsapp'
            ? $this->whatsapp->verifyOtp($pending['identifier'], $data['code'], 'reset_password')
            : $this->emailOtp->verifyOtp($pending['identifier'], $data['code'], 'reset_password');

        if (($result['result'] ?? '0') !== '1') {
            // One message for every failure mode. A wrong code, a burnt code,
            // an expired code and "no code was ever sent because the account
            // does not exist" must be indistinguishable, otherwise this step
            // enumerates accounts.
            return back()
                ->withErrors(['code' => 'That code is not valid or has expired. Please request a new one.'])
                ->withInput();
        }

        $user = $pending['channel'] === 'email'
            ? User::query()->where('email', $pending['identifier'])->first()
            : user_by_phone($pending['identifier']);

        if (! $user || $user->status !== 'active') {
            $request->session()->forget('password_reset');

            return redirect()->route('password.request')
                ->withErrors(['identifier' => __('We can\'t reset the password for that account.')]);
        }

        $request->session()->put('password_reset.user_id', $user->id);

        return redirect()->route('password.new');
    }

    /** Step 3 — new password + confirmation, shown only after a verified code. */
    public function showNewPassword(): View|RedirectResponse
    {
        if ($this->verifiedResetUserId() === null) {
            return redirect()->route('password.request');
        }

        return view('storefront.auth.new-password');
    }

    /** Step 3 submit — store the new password. Deliberately does not log in. */
    public function updatePassword(Request $request): RedirectResponse
    {
        if ($this->verifiedResetUserId() === null) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', $this->passwordRule()],
            'password_confirmation' => ['required', 'same:password'],
        ], $this->messages());

        $user = User::query()->find($this->verifiedResetUserId());

        if (! $user || $user->status !== 'active') {
            $request->session()->forget('password_reset');

            return redirect()->route('password.request')
                ->withErrors(['identifier' => __('We can\'t reset the password for that account.')]);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $user->setRememberToken(Str::random(60));
        event(new PasswordReset($user));

        $this->invalidateResetArtifacts($user);

        // No auth('web')->login() here on purpose: changing a password must
        // never hand out a session. The customer signs in with the new
        // password on the login page like any other customer.
        return $this->passwordChanged($request);
    }

    /** Step 4 — "password changed" confirmation with a way back to login. */
    public function showPasswordComplete(Request $request): View|RedirectResponse
    {
        if (! $request->session()->pull('password_changed', false)) {
            return redirect()->route('login');
        }

        return view('storefront.auth.password-changed');
    }

    public function showReset(string $token): View
    {
        return view('storefront.auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['nullable', 'required_without:phone', 'email'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password_confirmation.same' => 'The password confirmation does not match.',
            'phone.required_without' => 'Please enter your email address or mobile number.',
            'email.required_without' => 'Please enter your email address or mobile number.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number.',
            'phone.max' => 'Please enter a mobile number up to 15 digits.',
        ]);

        // ── phone-only reset: the OTP flow has already verified the phone, so
        //    look the user up by phone and verify the broker token directly.
        //    The token repository keys on getEmailForPasswordReset(), which
        //    falls back to the phone for accounts without an email.
        if ($request->filled('phone')) {
            $phone = canonical_phone($request->string('phone')->toString());
            $user = $phone ? user_by_phone($phone) : null;

            if (! $user) {
                return back()->withErrors(['email' => __('We can\'t find a user with that mobile number.')]);
            }

            if ($user->status !== 'active') {
                return back()->withErrors(['email' => __('Your account has been suspended. Contact support.')]);
            }

            $repository = Password::broker('users')->getRepository();

            if (! $repository->exists($user, (string) $request->input('token'))) {
                return back()->withErrors(['email' => __('This password reset link is invalid or has expired.')]);
            }

            $user->forceFill(['password' => Hash::make($data['password'])])->save();
            $user->setRememberToken(Str::random(60));
            event(new PasswordReset($user));
            $repository->delete($user);

            $this->rememberCanonicalPhone($user, $phone);
            $this->invalidateResetArtifacts($user);

            return $this->passwordChanged($request);
        }

        $request->merge(['email' => Str::lower(trim($request->string('email')->toString()))]);

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ], $this->messages());

        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user && $user->status !== 'active') {
            return back()->withErrors(['email' => __('Your account has been suspended. Contact support.')]);
        }

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->passwordChanged($request)
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Rewrite a legacy +91 / leading-zero phone to the 10-digit form when that
     * value is not already taken by another account.
     */
    protected function rememberCanonicalPhone(User $user, ?string $raw): void
    {
        $canonical = canonical_phone($raw);

        if ($canonical === null || $user->phone === $canonical) {
            return;
        }

        $taken = User::query()->where('phone', $canonical)->whereKeyNot($user->id)->exists();

        if ($taken) {
            return;
        }

        $user->forceFill(['phone' => $canonical])->save();
    }

    /**
     * The in-flight reset: where the code was sent and over which channel.
     *
     * @return array{identifier: string, channel: string}|null
     */
    protected function pendingReset(): ?array
    {
        $pending = session('password_reset');

        if (! is_array($pending) || empty($pending['identifier']) || empty($pending['channel'])) {
            return null;
        }

        return $pending;
    }

    /**
     * The account unlocked by a verified code, if any. Read from the session
     * only — never from request input — so a visitor cannot nominate whose
     * password gets changed.
     */
    protected function verifiedResetUserId(): ?int
    {
        $id = session('password_reset.user_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * Burn every outstanding reset artefact for the account: the broker's reset
     * links plus any reset code that is still live. Without this a link or code
     * dispatched a moment before the change could still be replayed.
     */
    protected function invalidateResetArtifacts(User $user): void
    {
        Password::broker('users')->getRepository()->delete($user);

        $codes = OtpCode::query()
            ->where('purpose', 'reset_password')
            ->whereNull('used_at')
            ->where(function ($query) use ($user) {
                if (! empty($user->phone)) {
                    $query->orWhere('phone', $user->phone);
                }

                if (! empty($user->email)) {
                    $query->orWhere('email', $user->email);
                }
            });

        foreach ($codes->get() as $code) {
            $code->update(['used_at' => now()]);
        }
    }

    /**
     * Clear the reset state and hand the guest to the confirmation page.
     * The caller is responsible for having already saved the new password.
     */
    protected function passwordChanged(Request $request): RedirectResponse
    {
        $request->session()->forget('password_reset');
        $request->session()->flash('password_changed', true);

        return redirect()->route('password.complete');
    }
}
