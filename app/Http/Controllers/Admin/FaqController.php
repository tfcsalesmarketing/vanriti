<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->paginate(50);

        $faqs->getCollection()->transform(function ($faq) {
            $faq->category_group = $faq->category ?: 'Uncategorized';

            return $faq;
        });

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        $categories = $this->getCategories();
        $linkCategories = Category::active()->orderBy('name')->get();
        $linkProducts = Product::active()->orderBy('name')->get();

        return view('admin.faqs.create', compact('categories', 'linkCategories', 'linkProducts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'related_categories' => 'nullable|array',
            'related_categories.*' => 'exists:categories,id',
            'related_products' => 'nullable|array',
            'related_products.*' => 'exists:products,id',
        ]);

        $relatedCategories = $validated['related_categories'] ?? [];
        $relatedProducts = $validated['related_products'] ?? [];
        unset($validated['related_categories'], $validated['related_products']);

        $faq = Faq::create($validated);
        $faq->categories()->sync($relatedCategories);
        $faq->products()->sync($relatedProducts);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): View
    {
        $categories = $this->getCategories();
        $linkCategories = Category::active()->orderBy('name')->get();
        $linkProducts = Product::active()->orderBy('name')->get();

        return view('admin.faqs.edit', compact('faq', 'categories', 'linkCategories', 'linkProducts'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'related_categories' => 'nullable|array',
            'related_categories.*' => 'exists:categories,id',
            'related_products' => 'nullable|array',
            'related_products.*' => 'exists:products,id',
        ]);

        $relatedCategories = $validated['related_categories'] ?? [];
        $relatedProducts = $validated['related_products'] ?? [];
        unset($validated['related_categories'], $validated['related_products']);

        $faq->update($validated);
        $faq->categories()->sync($relatedCategories);
        $faq->products()->sync($relatedProducts);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'FAQ deleted successfully.');
    }

    private function getCategories(): array
    {
        $existing = DB::table('faqs')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values()
            ->toArray();

        $defaults = [
            'Orders & Delivery',
            'Payments',
            'Returns & Refunds',
            'Products',
            'Account & Support',
        ];

        return array_values(array_unique(array_merge($existing, $defaults)));
    }
}
