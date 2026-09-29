@php
    $visitors = $report['visitors'] ?? [];
    $prevVisitors = $report['compare']['visitors'] ?? [];
    $series = $visitors['series'] ?? [];

    $newRate = $visitors['new_rate'] ?? null;
    $returningRate = $visitors['returning_rate'] ?? null;
    $prevNewRate = $prevVisitors['new_rate'] ?? null;
    $prevReturningRate = $prevVisitors['returning_rate'] ?? null;

    $coverageNote = $report['tracking']['visitor_coverage_note'] ?? '';
    $hasVisitors = (int) ($visitors['unique'] ?? 0) > 0;
@endphp

@if (($report['visitors']['error'] ?? false))
    <div class="alert alert-warning py-2 small">{{ $report['visitors']['message'] }}</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card table-card">
                <div class="card-header">Visitor Trends</div>
                <div class="card-body">
                    @if ($hasVisitors)
                        <canvas id="visitorTrendChart" height="120"></canvas>
                    @else
                        <div class="empty-state py-5"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No visitor-identified page views for the selected period.</p></div>
                    @endif

                    <div class="row g-3 mt-1">
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">New Visitor Rate</div>
                            <div class="fw-semibold">{{ $newRate === null ? '&mdash;' : number_format((float) $newRate, 1).'%' }}</div>
                            <div class="small text-muted">New ÷ Website Visitors</div>
                            @if ($prevNewRate !== null)
                                <div class="small text-muted">prev {{ number_format((float) $prevNewRate, 1).'%' }}</div>
                            @endif
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Returning Visitor Rate</div>
                            <div class="fw-semibold">{{ $returningRate === null ? '&mdash;' : number_format((float) $returningRate, 1).'%' }}</div>
                            <div class="small text-muted">Returning ÷ Website Visitors</div>
                            @if ($prevReturningRate !== null)
                                <div class="small text-muted">prev {{ number_format((float) $prevReturningRate, 1).'%' }}</div>
                            @endif
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Sessions per Visitor</div>
                            <div class="fw-semibold">
                                @if ($hasVisitors && (int) $visitors['sessions'] > 0)
                                    {{ number_format((int) $visitors['sessions'] / (int) $visitors['unique'], 2) }}
                                @else
                                    &mdash;
                                @endif
                            </div>
                            <div class="small text-muted">Sessions ÷ Website Visitors</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Repeat Visit Ratio</div>
                            <div class="fw-semibold">{{ $returningRate === null ? '&mdash;' : number_format((float) $returningRate, 1).'%' }}</div>
                            <div class="small text-muted">Returning ÷ Website Visitors</div>
                        </div>
                    </div>

                    @if ($coverageNote)
                        <div class="alert alert-info py-2 px-3 small mt-3 mb-0 d-flex gap-2 align-items-start" role="note">
                            <i class="bi bi-info-circle-fill mt-1"></i>
                            <span>{{ $coverageNote }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card table-card h-100">
                <div class="card-header">New vs Returning by Day</div>
                <div class="card-body p-0">
                    @if (count($series))
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">Date</th>
                                    <th class="text-end small">Visitors</th>
                                    <th class="text-end small">New</th>
                                    <th class="text-end small">Returning</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($series as $day)
                                    <tr>
                                        <td class="small">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                        <td class="text-end small fw-semibold">{{ number_format((int) $day['visitors']) }}</td>
                                        <td class="text-end small text-primary">{{ number_format((int) $day['new']) }}</td>
                                        <td class="text-end small text-success">{{ number_format((int) $day['returning']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state py-4"><i class="bi bi-inbox"></i><p class="mt-2 mb-0 small">No data available for the selected period.</p></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
