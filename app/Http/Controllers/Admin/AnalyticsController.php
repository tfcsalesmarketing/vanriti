<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsCommandCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'preset' => ['sometimes', 'nullable', 'string', 'in:today,yesterday,last_7,last_30,this_month,last_month,custom'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $preset = $data['preset'] ?? 'last_30';

        $range = $this->resolveRange($preset, $data['from'] ?? null, $data['to'] ?? null, $request);
        if ($range instanceof RedirectResponse) {
            return $range;
        }

        [$from, $to] = $range;

        $service = app(AnalyticsCommandCenterService::class);
        $report = $service->report($from, $to);
        $report['products']['report'] = $service->productReport($from, $to, (int) $request->input('page', 1));

        return view('admin.analytics.index', compact('preset', 'from', 'to', 'report'));
    }

    /**
     * Resolves a preset (or custom range) into inclusive 'Y-m-d' bounds,
     * interpreted in the application timezone. Falls back to Last 30 Days
     * when no preset is supplied (server-side default).
     */
    protected function resolveRange(string $preset, ?string $from, ?string $to, Request $request): array|RedirectResponse
    {
        $today = Carbon::now();

        return match ($preset) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'yesterday' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
            'last_7' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'custom' => $this->resolveCustomRange($from, $to, $request),
            default => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
        };
    }

    protected function resolveCustomRange(?string $from, ?string $to, Request $request): array|RedirectResponse
    {
        if (! $from || ! $to) {
            return back()->withErrors(['range' => 'Please provide both start and end dates for a custom range.'])->withInput();
        }

        $fromDate = Carbon::parse($from);
        $toDate = Carbon::parse($to);

        if ($fromDate->greaterThan($toDate)) {
            return back()->withErrors(['range' => 'The start date cannot be after the end date.'])->withInput();
        }

        $days = $fromDate->startOfDay()->diffInDays($toDate->startOfDay()) + 1;

        if ($days > app(AnalyticsCommandCenterService::class)->maxRangeDays()) {
            return back()->withErrors([
                'range' => 'The maximum allowed range is '.app(AnalyticsCommandCenterService::class)->maxRangeDays().' days.',
            ])->withInput();
        }

        return [$from, $to];
    }
}
