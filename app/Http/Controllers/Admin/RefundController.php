<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\ActivityLogger;
use App\Services\NotificationService;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    protected array $statuses = [
        'requested', 'under_review', 'approved', 'processing', 'completed', 'rejected',
    ];

    public function __construct(
        protected RefundService $refundService,
        protected ActivityLogger $logger,
    ) {
    }

    public function index(Request $request): View
    {
        $refunds = Refund::query()
            ->with(['user', 'order', 'returnRequest'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where(function ($query) use ($q) {
                    $query->where('refund_number', 'like', "%{$q}%")
                        ->orWhereHas('order', function ($query) use ($q) {
                            $query->where('order_number', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'statuses' => $this->statuses,
        ]);
    }

    public function approve(Request $request, Refund $refund)
    {
        $adminNote = $request->input('admin_note');

        $this->refundService->approve($refund, $adminNote);
        $this->logger->refundProcessed(auth('admin')->user(), $refund);
        app(NotificationService::class)->refundStatusChanged($refund);

        return redirect()->back()->with('success', 'Refund approved.');
    }

    public function reject(Request $request, Refund $refund)
    {
        $adminNote = $request->input('admin_note');

        $this->refundService->reject($refund, $adminNote);
        $this->logger->refundProcessed(auth('admin')->user(), $refund);
        app(NotificationService::class)->refundStatusChanged($refund);

        return redirect()->back()->with('success', 'Refund rejected.');
    }

    public function process(Refund $refund)
    {
        $this->refundService->process($refund);
        $this->logger->refundProcessed(auth('admin')->user(), $refund);

        return redirect()->back()->with('success', 'Refund processing.');
    }

    public function complete(Request $request, Refund $refund)
    {
        try {
            $this->refundService->complete($refund, $request->input('gateway_reference'));
            $this->logger->refundProcessed(auth('admin')->user(), $refund);

            return redirect()->back()->with('success', 'Refund completed.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Refund could not be completed: '.$e->getMessage());
        }
    }
}
