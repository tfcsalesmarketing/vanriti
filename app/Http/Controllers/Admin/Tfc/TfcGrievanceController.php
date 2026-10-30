<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcGrievanceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcGrievanceController extends Controller
{
    public function index(Request $request): View
    {
        $query = TfcGrievanceTicket::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('consumer_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('admin.tfc.grievances.index', compact('tickets'));
    }

    public function show(TfcGrievanceTicket $ticket): View
    {
        return view('admin.tfc.grievances.show', compact('ticket'));
    }

    public function update(Request $request, TfcGrievanceTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:acknowledged,under_investigation,resolved'],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validated['status'] === 'resolved' && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);

        return back()->with('success', 'Grievance ticket updated successfully.');
    }
}
