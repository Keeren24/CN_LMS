@php($alerts = $classAlerts ?? ['cancelled' => [], 'nextClass' => null, 'events' => []])

@if (! empty($alerts['cancelled']))
    <div class="alert alert-danger d-flex align-items-start" role="alert">
        <i class="ti ti-alert-triangle me-2 mt-1"></i>
        <div>
            <strong>Class cancelled</strong>
            @foreach ($alerts['cancelled'] as $c)
                <div class="small">{{ $c['date'] }}@if (! empty($c['reason'])) — {{ $c['reason'] }}@endif</div>
            @endforeach
        </div>
    </div>
@endif

@if (! empty($alerts['nextClass']))
    <div class="alert alert-primary d-flex align-items-center" role="alert">
        <i class="ti ti-calendar-event me-2"></i>
        <div>
            Next class: <strong>{{ $alerts['nextClass']['date'] }}</strong>@if ($alerts['nextClass']['time']) at {{ $alerts['nextClass']['time'] }}@endif
            @if ($alerts['nextClass']['isThisWeek'])
                <span class="badge bg-label-primary ms-1">this week</span>
            @endif
        </div>
    </div>
@endif

@if (! empty($alerts['events']))
    <div class="alert alert-info d-flex align-items-start flex-wrap" role="alert">
        <i class="ti ti-confetti me-2 mt-1"></i>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span>Upcoming:</span>
            @foreach ($alerts['events'] as $e)
                <span class="badge" style="background:{{ $e['color'] }};color:#fff">{{ $e['title'] }} · {{ $e['date'] }}</span>
            @endforeach
        </div>
    </div>
@endif
