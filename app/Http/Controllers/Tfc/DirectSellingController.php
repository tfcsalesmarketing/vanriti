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
     * Page 1: Direct Selling with TFC.
     * Comprehensive educational guide on ethical direct selling with VANRITI products.
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
     * Page 2: TFC Incentive & Rewards Program.
     * Full business plan explaining Product Credit (PC), activation packages, binary matching, and rules.
     */
    public function incentiveProgram(): View
    {
        return view('tfc.direct-selling.incentive-program');
    }

    /**
     * The TFC Direct Selling Opportunity (Backward compatibility alias).
     */
    public function opportunity(): View
    {
        return $this->incentiveProgram();
    }

    /**
     * How Direct Selling Works with TFC.
     */
    public function howItWorks(): View
    {
        return $this->directSelling();
    }

    /**
     * Seller Application Page.
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
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        TfcInquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'subject' => 'Direct Seller Prospective Application',
            'message' => $validated['message'] ?? 'Prospective application submitted via website.',
            'type' => 'seller_application',
            'city' => $validated['city'],
            'state' => $validated['state'],
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Thank you for your interest! Your application has been received. Our team will review your details and contact you shortly.');
    }

    /**
     * Direct Seller Benefits & Values.
     */
    public function benefits(): View
    {
        return view('tfc.direct-selling.benefits');
    }
}
