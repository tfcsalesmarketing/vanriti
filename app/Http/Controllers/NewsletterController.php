<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        try {
            $existing = Newsletter::where('email', $request->email)->first();

            if ($existing) {
                $existing->update([
                    'is_subscribed' => true,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            } else {
                Newsletter::create([
                    'email' => $request->email,
                    'is_subscribed' => true,
                    'subscribed_at' => now(),
                ]);
            }
        } catch (QueryException $e) {
            // Unique race condition — still success
        }

        return back()->with('success', 'Thanks for subscribing!');
    }
}
