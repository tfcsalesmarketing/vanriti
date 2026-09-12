<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class InfoController extends Controller
{
    public function about(): View
    {
        return view('storefront.pages.about');
    }

    public function privacyPolicy(): View
    {
        return view('storefront.pages.privacy-policy');
    }

    public function terms(): View
    {
        return view('storefront.pages.terms');
    }

    public function shipping(): View
    {
        return view('storefront.pages.shipping');
    }
}