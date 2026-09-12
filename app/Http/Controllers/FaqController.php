<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::active()->get()->groupBy(fn ($f) => $f->category ?? 'General');

        return view('storefront.faq.index', compact('faqs'));
    }
}
