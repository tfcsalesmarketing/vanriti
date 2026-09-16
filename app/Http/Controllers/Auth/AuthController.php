<?php

namespace App\Http\Controllers\Auth;

use App\Consent\ConsentService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
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
    ) {}

    public function showLogin(): View
    {
        return view('storefront.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], $this->messages());

        $email = Str::lower(trim($credentials['email']));
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(
                $user
                    ? ['password' => 'The password you entered is incorrect.']
                    : ['email' => 'No account found with this email address.']
            )->onlyInput('email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account has been suspended. Contact support.']);
        }

        auth('web')->login($user, $request->boolean('remember'));

        $user->update(['last_login_at' => now()]);
        session()->regenerate();
        session()->regenerateToken();

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->wishlistService->mergeGuestIntoUser($user);
        $this->consentService->mergeAnonymousIntoUser($user);

        return redirect()->intended(route('account.dashboard'));
    }

    public function showRegister(): View
    {
        return view('storefront.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ], $this->messages());

        $phone = Str::of($data['phone'])->replace([' ', '-'], '')->trim()->toString();

        $email = ! empty($data['email'] ?? null)
            ? Str::lower(trim($data['email']))
            : null;

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'phone' => $phone,
            'password' => $data['password'],
            'status' => 'active',
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
        $context = in_array($request->input('context'), ['register', 'forgot', 'reset'], true)
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

        if ($context === 'login' && $request->exists('email') && $request->exists('password')) {
            $email = Str::lower(trim((string) $request->input('email')));
            $user = User::where('email', $email)->first();

            if (! $user) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['email' => ['No account found with this email address.']],
                ], 422);
            }

            if (! Hash::check((string) $request->input('password'), $user->password)) {
                return response()->json([
                    'valid' => false,
                    'errors' => ['password' => ['The password you entered is incorrect.']],
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
                'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
                'password' => $password,
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        if ($context === 'forgot') {
            return [
                'email' => ['required', 'email'],
            ];
        }

        if ($context === 'reset') {
            $password = ['required', $this->passwordRule()];
            if ($request->exists('password') && $request->exists('password_confirmation')) {
                $password[] = 'confirmed';
            }

            return [
                'email' => ['required', 'email'],
                'password' => $password,
                'password_confirmation' => ['required', 'same:password'],
            ];
        }

        return [
            'email' => ['required', 'email'],
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
        ];
    }

    public function logout(): RedirectResponse
    {
        auth('web')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }

    public function showForgot(): View
    {
        return view('storefront.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim($request->string('email')->toString()))]);
        $request->validate(['email' => ['required', 'email']], $this->messages());

        try {
            $status = Password::broker('users')->sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::warning('Password reset email could not be delivered.', [
                'email' => $request->string('email')->toString(),
                'error' => $e->getMessage(),
            ]);
            $status = null;
        }

        if ($status !== Password::RESET_LINK_SENT) {
            Log::info('Password reset requested for an unknown or throttled email address.', [
                'email' => $request->string('email')->toString(),
                'status' => $status,
            ]);
        }

        return back()->with('success', __('We have emailed your password reset link.'));
    }

    public function showReset(string $token): View
    {
        return view('storefront.auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim($request->string('email')->toString()))]);

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ], $this->messages());

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                event(new PasswordReset($user));
                auth('web')->login($user);
                $request->session()->regenerate();
                $request->session()->regenerateToken();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account.dashboard')->with('success', __('Your password has been reset.'))
            : back()->withErrors(['email' => __($status)]);
    }
}
