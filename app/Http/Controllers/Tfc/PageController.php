<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a statutory policy or CMS page.
     * Pages in 'draft' status show an official legal review notice.
     */
    public function show(string $slug, Request $request): View
    {
        $page = TfcPage::where('slug', $slug)->firstOrFail();

        $isAdmin = auth('admin')->check();
        $isDraft = ($page->status === 'draft');

        return view('tfc.pages.show', compact('page', 'isDraft', 'isAdmin'));
    }
}
