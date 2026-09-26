<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:120',
        ]);

        $email = normalize_email($data['email']);

        try {
            $existing = Newsletter::where('email', $email)->first();

            if ($existing) {
                $existing->update([
                    'is_subscribed' => true,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            } else {
                Newsletter::create([
                    'email' => $email,
                    'name' => $data['name'] ?? null,
                    'is_subscribed' => true,
                    'subscribed_at' => now(),
                ]);
            }
        } catch (QueryException $e) {
            // A concurrent request for the same address wins the unique index.
            // Re-read: if the row now exists the subscription really is in
            // place, so this is still a success rather than a silent failure.
            $existing = Newsletter::where('email', $email)->first();

            if (! $existing) {
                return back()->with('error', 'We could not save your subscription right now. Please try again.');
            }

            $existing->update([
                'is_subscribed' => true,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        return back()->with('success', 'Thanks for subscribing!');
    }

    public function unsubscribe(Request $request, string $token)
    {
        $raw = (string) $request->query('email', '');
        $email = normalize_email($raw);

        abort_unless(
            $email !== '' && hash_equals((string) $token, newsletter_unsubscribe_token($email)),
            403
        );

        // Rows stored before normalization existed are matched too, otherwise
        // an existing subscriber could never unsubscribe.
        Newsletter::whereIn('email', array_values(array_unique([$email, Str::lower(trim($raw))])))->update([
            'is_subscribed' => false,
            'unsubscribed_at' => now(),
        ]);

        return view('storefront.unsubscribed');
    }
}
