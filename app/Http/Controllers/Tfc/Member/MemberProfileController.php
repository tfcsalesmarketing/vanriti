<?php

namespace App\Http\Controllers\Tfc\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MemberProfileController extends Controller
{
    public function show(): View
    {
        $member = Auth::user()->directSellingMember;
        $user = Auth::user();

        return view('tfc.member.profile', [
            'member' => $member,
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $member = Auth::user()->directSellingMember;
        $user = Auth::user();

        $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'regex:/^[1-9][0-9]{5}$/'],
        ]);

        $member->update([
            'full_name' => $request->input('full_name'),
            'address_line' => $request->input('address_line'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'pincode' => $request->input('pincode'),
        ]);

        $user->update([
            'name' => $request->input('full_name'),
        ]);

        return back()->with('success', 'Profile information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
