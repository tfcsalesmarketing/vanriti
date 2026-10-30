<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcGrievanceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrievanceController extends Controller
{
    /**
     * Consumer Grievance Portal (Rule 7, Direct Selling Rules 2021).
     */
    public function index(): View
    {
        return view('tfc.grievance.index');
    }

    /**
     * Submit a consumer grievance ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'consumer_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'order_number' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'in:product_defect,delivery_delay,direct_seller_conduct,refund_issue,other'],
            'complaint_details' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $ticketNumber = TfcGrievanceTicket::generateTicketNumber();

        $ticket = TfcGrievanceTicket::create([
            'ticket_number' => $ticketNumber,
            'consumer_name' => $validated['consumer_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'order_number' => $validated['order_number'] ?? null,
            'category' => $validated['category'],
            'complaint_details' => $validated['complaint_details'],
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        return redirect()->route(request()->routeIs('tfc.preview.*') ? 'tfc.preview.grievance.track' : 'tfc.grievance.track')
            ->with('ticket_success', [
                'ticket_number' => $ticket->ticket_number,
                'consumer_name' => $ticket->consumer_name,
                'acknowledged_at' => $ticket->acknowledged_at->format('d M Y, h:i A'),
                'message' => 'Your complaint has been acknowledged within statutory direct selling guidelines. Please keep your Ticket Reference Number for tracking.'
            ]);
    }

    /**
     * Grievance tracking form.
     */
    public function track(): View
    {
        return view('tfc.grievance.track');
    }

    /**
     * Search and display grievance ticket status.
     */
    public function trackStatus(Request $request): View
    {
        $request->validate([
            'ticket_number' => ['required', 'string', 'max:50'],
        ]);

        $ticket = TfcGrievanceTicket::where('ticket_number', trim($request->ticket_number))->first();

        return view('tfc.grievance.track', compact('ticket'));
    }
}
