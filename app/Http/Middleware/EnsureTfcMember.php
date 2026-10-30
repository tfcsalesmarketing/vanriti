<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTfcMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            // Store intended URL so member returns after login
            return redirect()->guest(route('tfc.preview.member.login'))
                ->with('error', 'Please log in with your TFC Member credentials to access the Member Portal.');
        }

        $user = Auth::user();

        // Check if authenticated user is a registered direct selling member
        if (! $user->isTfcMember()) {
            return redirect()->route('tfc.preview.member.register')
                ->with('info', 'Your account is currently registered as a VANRITI customer. Please complete your Direct Seller registration below.');
        }

        $member = $user->directSellingMember;

        // Check member status
        if ($member->status === 'suspended') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tfc.preview.member.login')
                ->with('error', 'Your TFC Member account has been suspended. Please contact support.');
        }

        if ($member->status === 'terminated') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tfc.preview.member.login')
                ->with('error', 'Your TFC Member account is terminated.');
        }

        // Share the active direct selling member instance with views
        view()->share('currentMember', $member);

        return $next($request);
    }
}
