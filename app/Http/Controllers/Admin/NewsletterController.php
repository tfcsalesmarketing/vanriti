<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(): View
    {
        $newsletters = Newsletter::orderByDesc('subscribed_at')->paginate(20);

        return view('admin.newsletters.index', compact('newsletters'));
    }

    public function destroy(Newsletter $newsletter)
    {
        $newsletter->delete();

        return redirect()->back()->with('success', 'Newsletter subscription deleted.');
    }
}
