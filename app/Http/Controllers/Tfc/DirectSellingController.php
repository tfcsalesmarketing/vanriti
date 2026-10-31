<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tfc\TfcInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectSellingController extends Controller
{
    /**
     * About Direct Selling with TFC.
     */
    public function directSelling(): View
    {
        $rootCategories = Category::active()
            ->roots()
            ->with(['children'])
            ->orderBy('sort_order')
            ->get();

        $showcaseProducts = Product::active()
            ->with(['images', 'categories'])
            ->whereHas('images')
            ->latest()
            ->take(4)
            ->get();

        return view('tfc.direct-selling.index', compact('rootCategories', 'showcaseProducts'));
    }

    /**
     * Binary Business Plan & Incentive Program.
     */
    public function businessPlan(): View
    {
        return view('tfc.direct-selling.business-plan');
    }

    /**
     * Compatibility aliases.
     */
    public function incentiveProgram(): View
    {
        return $this->businessPlan();
    }

    public function opportunity(): View
    {
        return $this->businessPlan();
    }

    public function howItWorks(): View
    {
        return $this->directSelling();
    }

    public function benefits(): View
    {
        return $this->businessPlan();
    }

    /**
     * Prospective Seller Application Page.
     */
    public function apply(): View
    {
        return view('tfc.direct-selling.become-a-seller');
    }

    /**
     * Submit Seller Application.
     */
    public function submitApplication(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['type'] = 'seller_application';
        $validated['status'] = 'new';
        $validated['ip_address'] = $request->ip();

        TfcInquiry::create($validated);

        return redirect()->route('tfc.preview.member.register')
            ->with('success', 'Thank you for your interest! You can also complete your instant free direct seller registration right now below.');
    }
}
