@extends('layouts/layoutMaster')

@section('title', 'My Calendar')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('page-style')
    <style>
        .scal-card { background: var(--bs-card-bg); border: 1px solid var(--bs-border-color); border-radius: 1rem; padding: 1rem 1.25rem 1.25rem; }
        #studentCalendar { --fc-border-color: var(--bs-border-color); --fc-today-bg-color: rgba(79,70,229,0.06); max-width: 100%; }
        #studentCalendar .fc-view-harness { min-block-size: auto !important; }
        #studentCalendar .fc-toolbar-title { font-size: 1.2rem; font-weight: 600; }
        #studentCalendar .fc-daygrid-event { white-space: normal; border-radius: 6px; padding: 1px 5px; font-size: 0.72rem; font-weight: 500; }
        #studentCalendar .fc-event.ev-national { background-color:#4f46e5 !important; border-color:#4f46e5 !important; }
        #studentCalendar .fc-event.ev-state { background-color:#0e7490 !important; border-color:#0e7490 !important; }
        @foreach ($branches as $b)
        #studentCalendar .fc-event.ev-branch-{{ $b->id }} { background-color:{{ $b->color ?: '#16a34a' }} !important; border-color:{{ $b->color ?: '#16a34a' }} !important; }
        @endforeach
        #studentCalendar .fc-event.ev-session-cancelled { background-color:#dc2626 !important; border-color:#dc2626 !important; }
        #studentCalendar .fc-event.ev-session-center_break { background-color:#94a3b8 !important; border-color:#94a3b8 !important; }
        #studentCalendar .fc-event.ev-session-holiday { background-color:#7c3aed !important; border-color:#7c3aed !important; }
        #studentCalendar .fc-event.ev-session-cancelled .fc-event-title { text-decoration: line-through; }
        #studentCalendar .fc-event, #studentCalendar .fc-event .fc-event-title { color:#fff !important; font-weight:600 !important; }
        .scal-legend { display:flex; flex-wrap:wrap; gap:0.75rem 1.25rem; font-size:0.8rem; }
        .scal-legend span.dot { display:inline-block; width:0.7rem; height:0.7rem; border-radius:50%; margin-inline-end:0.35rem; vertical-align:middle; }
        .att-badge { display:inline-flex; align-items:center; justify-content:center; width:14px; height:14px; border-radius:50%; margin-inline-start:4px; margin-inline-end:0.35rem; vertical-align:middle; font-size:9px; color:#fff; }
        .att-present { background:#15803d; }
        .att-late { background:#b45309; }
        .att-absent { background:#991b1b; }
        #studentCalendar .fc-view-harness { position: relative; }
        .cal-popover-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1085; border-radius: inherit; }
        .up-row { display:flex; gap:0.75rem; align-items:flex-start; padding:0.65rem 0; border-bottom:1px solid var(--bs-border-color); }
        .up-row:last-child { border-bottom:0; }
        .up-date { flex:0 0 3.1rem; text-align:center; line-height:1.1; }
        .up-date .d { font-size:1.15rem; font-weight:700; }
        .up-date .m { font-size:0.68rem; text-transform:uppercase; color:var(--bs-secondary-color); }
        .up-bar { flex:0 0 3px; align-self:stretch; border-radius:3px; }
        @media (max-width:575.98px){ #studentCalendar .fc-toolbar{flex-direction:column;gap:0.5rem;} }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/student-calendar.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    @include('student.partials.class-alerts')

    {{-- ── Who and when ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex align-items-center gap-4">
            <div class="avatar avatar-lg flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-success">
                    <i class="ti ti-calendar-check ti-lg"></i>
                </span>
            </div>
            <div>
                <h5 class="mb-0">{{ $className ?: 'My Calendar' }}</h5>
                <small class="text-muted">Class schedule, holidays, events and attendance</small>
                @if ($classInfo && $classInfo->class_day)
                    <div class="text-muted small mt-1">
                        <i class="ti ti-clock ti-xs me-1"></i>
                        Every {{ ucfirst($classInfo->class_day) }}
                        @if ($classInfo->start_time && $classInfo->end_time)
                            &bull; {{ \Carbon\Carbon::parse($classInfo->start_time)->format('g:i A') }}
                            – {{ \Carbon\Carbon::parse($classInfo->end_time)->format('g:i A') }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Attendance at a glance ── --}}
    @if ($summary['held'] > 0)
        @php
            $pct = $summary['pct'];
            $pctColor = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
            $pctLabel = $pct >= 90 ? 'Excellent' : ($pct >= 75 ? 'Good' : 'Needs Attention');
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-body py-4">
                        <div class="display-6 fw-bold text-{{ $pctColor }}">{{ $pct }}%</div>
                        <div class="text-muted small">Attendance Rate</div>
                        <span class="badge bg-{{ $pctColor }} mt-2">{{ $pctLabel }}</span>
                        <div class="progress mt-3" style="height: 6px;">
                            <div class="progress-bar bg-{{ $pctColor }}" style="width: {{ $pct }}%"></div>
                        </div>
                        @if ($summary['unmarked'] > 0)
                            <div class="text-muted mt-2" style="font-size:0.7rem;">
                                Includes {{ $summary['unmarked'] }} {{ Str::plural('class', $summary['unmarked']) }}
                                the tutor has yet to mark
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 bg-label-success h-100 text-center shadow-sm">
                    <div class="card-body py-4">
                        <i class="ti ti-circle-check ti-lg text-success mb-2 d-block"></i>
                        <div class="fs-2 fw-bold text-success">{{ $summary['attended'] }}</div>
                        <div class="text-muted small">Present</div>
                        <div class="text-muted small">out of {{ $summary['held'] }} classes</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 bg-label-danger h-100 text-center shadow-sm">
                    <div class="card-body py-4">
                        <i class="ti ti-circle-x ti-lg text-danger mb-2 d-block"></i>
                        <div class="fs-2 fw-bold text-danger">{{ $summary['absent'] }}</div>
                        <div class="text-muted small">Absent</div>
                        @if ($summary['absent'] > 0)
                            <div class="text-muted small">{{ round($summary['absent'] / $summary['held'] * 100) }}% of classes</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card border-0 bg-label-warning h-100 text-center shadow-sm">
                    <div class="card-body py-4">
                        <i class="ti ti-clock ti-lg text-warning mb-2 d-block"></i>
                        <div class="fs-2 fw-bold text-warning">{{ $summary['late'] }}</div>
                        <div class="text-muted small">Late</div>
                        @if ($summary['late'] > 0)
                            <div class="text-muted small">{{ round($summary['late'] / $summary['held'] * 100) }}% of classes</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        {{-- ── The calendar ── --}}
        <div class="col-12 col-xl-8">
            <div class="scal-card h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div>
                        <h5 class="mb-0 fw-semibold">My Calendar</h5>
                        <small class="text-muted">{{ $className ? $className.' — classes, holidays & events' : 'Holidays & events' }}</small>
                    </div>
                    <div class="scal-legend">
                        <span><span class="dot" style="background:{{ $branchColor }}"></span>Class</span>
                        <span><span class="dot" style="background:#dc2626"></span>Cancelled</span>
                        <span><span class="dot" style="background:#94a3b8"></span>Break</span>
                        <span><span class="dot" style="background:#7c3aed"></span>Holiday</span>
                        <span><span class="att-badge att-present"><i class="ti ti-check"></i></span>Present</span>
                        <span><span class="att-badge att-late"><i class="ti ti-clock"></i></span>Late</span>
                        <span><span class="att-badge att-absent"><i class="ti ti-x"></i></span>Absent</span>
                    </div>
                </div>

                @unless ($hasClass)
                    <div class="alert alert-info mb-3">You're not assigned to a class yet — showing public holidays and events only.</div>
                @endunless

                <div id="studentCalendar" data-events-url="{{ route('student.calendar.events') }}"></div>
            </div>
        </div>

        {{-- ── Coming up & recent misses ── --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0 pb-2">
                    <h6 class="card-title mb-0"><i class="ti ti-calendar-time text-primary me-2"></i>Coming up</h6>
                </div>
                <div class="card-body pt-0">
                    @forelse ($upcoming as $day)
                        @php
                            [$label, $tone] = match ($day['status']) {
                                'cancelled'    => ['Class cancelled', 'danger'],
                                'center_break' => ['No class — break', 'secondary'],
                                'holiday'      => ['No class — '.($day['reason'] ?: 'public holiday'), 'purple'],
                                default        => ['Class as usual', 'success'],
                            };
                            $carbonDate = \Carbon\Carbon::parse($day['date']);
                        @endphp
                        <div class="up-row">
                            <div class="up-date">
                                <div class="d">{{ $carbonDate->format('d') }}</div>
                                <div class="m">{{ $carbonDate->format('M') }}</div>
                            </div>
                            <div class="up-bar" style="background:{{ in_array($day['status'], ['scheduled', 'completed']) ? $day['color'] : '#cbd5e1' }}"></div>
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">
                                    {{ $carbonDate->format('l') }}
                                    @if ($day['start_time'] && in_array($day['status'], ['scheduled', 'completed']))
                                        <span class="text-muted fw-normal">
                                            · {{ \Carbon\Carbon::parse($day['start_time'])->format('g:i A') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $label }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small py-3">Nothing scheduled yet.</div>
                    @endforelse
                </div>
            </div>

            @if (! empty($recentMissed))
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-alert-triangle text-warning me-2"></i>Recent absences / lates
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach ($recentMissed as $date => $rec)
                                <li class="list-group-item d-flex align-items-center gap-3 py-2">
                                    <span class="badge {{ $rec['status'] === 'absent' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ ucfirst($rec['status']) }}
                                    </span>
                                    <div>
                                        <div class="small fw-semibold">{{ \Carbon\Carbon::parse($date)->format('d M Y (D)') }}</div>
                                        @if ($rec['remarks'])
                                            <div class="small text-muted">{{ $rec['remarks'] }}</div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
