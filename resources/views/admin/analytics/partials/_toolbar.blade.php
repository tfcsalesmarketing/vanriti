@php
    $currentSection = request()->query('section', 'overview') ?: 'overview';
    $sections = [
        'overview' => 'Overview',
        'traffic' => 'Traffic',
        'product' => 'Product',
        'revenue' => 'Revenue & Orders',
        'diagnostics' => 'Tracking & Data',
    ];
    $baseQuery = request()->query();
    unset($baseQuery['page']);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h5 class="fw-bold mb-1">Analytics Command Center</h5>
        <p class="text-muted small mb-0">
            Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong> ({{ $report['period']['days'] }} days)
            &middot; compared with <strong>{{ $report['compare']['from'] }}</strong> to <strong>{{ $report['compare']['to'] }}</strong>.
            Aggregates are cached for up to 5 minutes &middot; generated {{ $report['period']['generated_at'] }} ({{ config('app.timezone') }}).
        </p>
    </div>

    <form method="GET" action="{{ route('admin.analytics') }}" class="d-flex flex-wrap align-items-end gap-2">
        <input type="hidden" name="section" value="{{ $currentSection }}">
        <div>
            <label class="form-label small text-muted mb-1" for="preset">Period</label>
            <select name="preset" id="preset" class="form-select form-select-sm" style="min-width:150px;">
                @foreach ([
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    'last_7' => 'Last 7 Days',
                    'last_30' => 'Last 30 Days',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'custom' => 'Custom Range',
                ] as $value => $label)
                    <option value="{{ $value }}" {{ $preset === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div id="customRangeWrap" class="{{ $preset === 'custom' ? '' : 'd-none' }}">
            <label class="form-label small text-muted mb-1" for="from">From</label>
            <input type="date" name="from" id="from" class="form-control form-control-sm" value="{{ request('from', $from) }}">
        </div>
        <div id="customRangeWrapTo" class="{{ $preset === 'custom' ? '' : 'd-none' }}">
            <label class="form-label small text-muted mb-1" for="to">To</label>
            <input type="date" name="to" id="to" class="form-control form-control-sm" value="{{ request('to', $to) }}">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Recomputes with the current filters (aggregates cached 5 minutes)">Refresh</button>
    </form>
</div>

@if ($errors->any())
    <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
@endif

<div class="mb-3">
    <ul class="nav segmented-nav-pills shadow-xs bg-white border p-1.5 flex-nowrap overflow-auto" style="border-radius: 9999px;">
        @foreach ($sections as $key => $label)
            <li class="nav-item">
                <a class="nav-link px-3.5 py-2 {{ $currentSection === $key ? 'active' : '' }}"
                   href="{{ route('admin.analytics', array_merge($baseQuery, ['section' => $key])) }}"
                   style="white-space: nowrap;">
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</div>