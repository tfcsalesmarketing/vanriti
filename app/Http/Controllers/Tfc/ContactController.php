<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Contact Us page.
     */
    public function index(): View
    {
        return view('tfc.contact');
    }

    /**
     * Submit general inquiry.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        TfcInquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'subject' => $validated['subject'] ?? 'General Corporate Inquiry',
            'message' => $validated['message'],
            'type' => 'general',
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Your message has been sent successfully. Our team will get back to you shortly.');
    }
}
