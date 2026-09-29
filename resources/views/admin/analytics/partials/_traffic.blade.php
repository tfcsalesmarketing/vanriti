@php
    $events = $report['events'] ?? [];
    $traffic = $report['traffic'] ?? [];
    $totalViews = (int) ($events['page_views'] ?? 0);
    $totalSessions = (int) ($events['visitors'] ?? 0);

    $groups = [
        'by_source' => 'Acquisition by Source',
        'by_medium' => 'Acquisition by Medium',
        'by_campaign' => 'Acquisition by Campaign',
        'by_landing' => 'Top Landing Pages',
    ];
    $rows = function (string $key) use ($traffic) {
        return collect($traffic[$key] ?? [])->map(fn ($item) => [
            'label' => $item['label'] ?? '(unknown)',
            'views' => (int) ($item['views'] ?? 0),
            'sessions' => (int) ($item['sessions'] ?? 0),
        ])->sortByDesc('views')->values();
    };
@endphp

<div class="row g-3 mb-4">
    @foreach ($groups as $key => $title)
        <div class="col-md-6">
            <div class="card table-card h-100">
                <div class="card-header">
                    <h6 class="m-0 fw-semibold">{{ $title }}</h6>
                </div>
                <div class="card-body p-0">
                    @if ($rows($key)->isEmpty())
                        <div class="empty-state p-4">
                            <div class="empty-state-icon"><i class="bi bi-signpost-2"></i></div>
                            <p class="fw-medium mb-1">No {{ strtolower($title) }} data yet</p>
                            <p class="text-muted mb-0 small">Traffic attribution is captured from UTM parameters, referrer host and the landing URL. Data appears once sessions are recorded in <code>analytics_events</code>.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Value</th>
                                        <th scope="col" class="text-end">Views</th>
                                        <th scope="col" class="text-end">Sessions</th>
                                        <th scope="col" style="min-width:120px;">Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-light">
                                        <td class="fw-semibold">All / (total)</td>
                                        <td class="text-end fw-semibold">{{ number_format($totalViews) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($totalSessions) }}</td>
                                        <td></td>
                                    </tr>
                                    @foreach ($rows($key) as $row)
                                        @php
                                            $share = $totalViews > 0 ? ($row['views'] / $totalViews) * 100 : 0;
                                        @endphp
                                        <tr title="{{ $row['label'] }}">
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ number_format($row['views']) }}</td>
                                            <td class="text-end">{{ number_format($row['sessions']) }}</td>
                                            <td>
                                                <div class="progress" style="height:6px;">
                                                    <div class="progress-bar bg-info" style="width: {{ $share }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card table-card mb-4">
    <div class="card-header">
        <h6 class="m-0 fw-semibold">Traffic by Device</h6>
    </div>
    <div class="card-body p-0">
        @if (empty($traffic['by_device']))
            <div class="empty-state p-4">
                <div class="empty-state-icon"><i class="bi bi-phone"></i></div>
                <p class="fw-medium mb-1">No device data yet</p>
                <p class="text-muted mb-0 small">Device type (mobile / tablet / desktop) is inferred from the User-Agent string.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Device</th>
                            <th scope="col" class="text-end">Views</th>
                            <th scope="col" class="text-end">Sessions</th>
                            <th scope="col" style="min-width:120px;">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (collect($traffic['by_device'])->sortByDesc('views') as $row)
                            @php $share = $totalViews > 0 ? (($row['views'] ?? 0) / $totalViews) * 100 : 0; @endphp
                            <tr>
                                <td class="fw-medium">{{ ucfirst($row['label'] ?? 'unknown') }}</td>
                                <td class="text-end">{{ number_format((int) ($row['views'] ?? 0)) }}</td>
                                <td class="text-end">{{ number_format((int) ($row['sessions'] ?? 0)) }}</td>
                                <td>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-secondary" style="width: {{ $share }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>