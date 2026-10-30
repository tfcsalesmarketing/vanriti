<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcInquiryController extends Controller
{
    public function index(Request $request): View
    {
        $query = TfcInquiry::latest();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $inquiries = $query->paginate(20)->withQueryString();

        return view('admin.tfc.inquiries.index', compact('inquiries'));
    }

    public function show(TfcInquiry $inquiry): View
    {
        return view('admin.tfc.inquiries.show', compact('inquiry'));
    }

    public function update(Request $request, TfcInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,in_progress,resolved'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $inquiry->update($validated);

        return back()->with('success', 'Inquiry status updated successfully.');
    }

    public function destroy(TfcInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();
        return redirect()->route('admin.tfc.inquiries.index')->with('success', 'Inquiry deleted successfully.');
    }
}
