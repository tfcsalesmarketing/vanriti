<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected CartService $cartService, protected WishlistService $wishlistService)
    {
    }

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

        if (! auth('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $user = auth('web')->user();

        if ($user->status !== 'active') {
            auth('web')->logout();

            return back()->withErrors(['email' => 'Your account has been suspended. Contact support.']);
        }

        $user->update(['last_login_at' => now()]);
        session()->regenerate();

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->wishlistService->mergeGuestIntoUser($user);

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ], $this->messages());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
        ]);

        auth('web')->login($user);
        session()->regenerate();

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->wishlistService->mergeGuestIntoUser($user);

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
            if ($request->has($field)) {
                $subset[$field] = $rule;
            }
        }

        $validator = Validator::make($request->only(array_keys($subset)), $subset, $this->messages());

        if ($validator->fails()) {
            return response()->json(['valid' => false, 'errors' => $validator->errors()->toArray()], 422);
        }

        return response()->json(['valid' => true]);
    }

    protected function rulesFor(string $context, Request $request): array
    {
        if ($context === 'register') {
            $password = ['required', Rules\Password::min(8)];
            if ($request->has('password') && $request->has('password_confirmation')) {
                $password[] = 'confirmed';
            }

            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
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
            $password = ['required', Rules\Password::min(8)];
            if ($request->has('password') && $request->has('password_confirmation')) {
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

    protected function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password_confirmation.same' => 'The password confirmation does not match.',
            'name.required' => 'Please enter your full name.',
            'name.max' => 'Name must not exceed 255 characters.',
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
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('users')->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showReset(string $token): View
    {
        return view('storefront.auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                event(new PasswordReset($user));
                auth('web')->login($user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account.dashboard')->with('success', __('Your password has been reset.'))
            : back()->withErrors(['email' => __($status)]);
    }
}