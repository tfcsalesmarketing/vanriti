<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(Page $page)
    {
        abort_if($page->status !== 'published', 404);

        return view('storefront.page', compact('page'));
    }

    public function returnPolicy()
    {
        $page = Page::published()->where('slug', 'LIKE', '%return%')->first();

        if ($page) {
            return view('storefront.page', ['page' => $page]);
        }

        $content = setting('return_policy', 'Return policy information is not available at this time.');

        return view('storefront.policies', compact('content'));
    }
}
