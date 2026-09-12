<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController extends Controller
{
    protected array $statuses = [
        'requested', 'under_review', 'approved', 'rejected', 'pickup_scheduled', 'picked_up', 'returned',
    ];

    public function __construct(
        protected RefundService $refundService,
    ) {
    }

    public function index(Request $request): View
    {
        $returns = ReturnRequest::query()
            ->with(['user', 'order', 'items'])
            ->withCount('items')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.returns.index', [
            'returns' => $returns,
            'statuses' => $this->statuses,
        ]);
    }

    public function show(ReturnRequest $returnRequest): View
    {
        $returnRequest->load(['items.orderItem', 'user', 'order', 'refunds']);

        return view('admin.returns.show', compact('returnRequest'));
    }

    public function approve(Request $request, ReturnRequest $returnRequest)
    {
        try {
            $returnRequest->update([
                'status' => 'approved',
                'admin_note' => $request->input('admin_note'),
                'processed_at' => now(),
            ]);

            $amount = round($returnRequest->items()->sum('refund_amount'), 2);

            if ($amount > 0) {
                $this->refundService->createFromReturn($returnRequest, $returnRequest->user, $amount, 'full', $returnRequest->reason);
            }

            app(NotificationService::class)->returnStatusChanged($returnRequest, 'approved');

            return redirect()->back()->with('success', 'Return approved and refund created.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Return could not be approved: '.$e->getMessage());
        }
    }

    public function reject(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->update([
            'status' => 'rejected',
            'admin_note' => $request->input('admin_note'),
            'processed_at' => now(),
        ]);

        app(NotificationService::class)->returnStatusChanged($returnRequest, 'rejected');

        return redirect()->back()->with('success', 'Return rejected.');
    }

    public function requestInfo(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->update([
            'status' => 'under_review',
            'admin_note' => $request->input('admin_note'),
        ]);

        app(NotificationService::class)->returnStatusChanged($returnRequest, 'under_review');

        return redirect()->back()->with('success', 'Return marked for review.');
    }

    public function markPickup(Request $request, ReturnRequest $returnRequest)
    {
        $returnRequest->update([
            'status' => 'pickup_scheduled',
        ]);

        return redirect()->back()->with('success', 'Pickup scheduled.');
    }

    public function markReturned(ReturnRequest $returnRequest)
    {
        $returnRequest->update([
            'status' => 'returned',
            'processed_at' => now(),
        ]);

        app(NotificationService::class)->returnStatusChanged($returnRequest, 'returned');

        return redirect()->back()->with('success', 'Return marked as received.');
    }
}
