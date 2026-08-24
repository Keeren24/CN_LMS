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
        #studentCalendar .fc-toolbar-title { font-size: 1.2rem; font-weight: 600; }
        #studentCalendar .fc-daygrid-event { white-space: normal; border-radius: 6px; padding: 1px 5px; font-size: 0.72rem; font-weight: 500; }
        #studentCalendar .fc-event.ev-national { background-color:#4f46e5 !important; border-color:#4f46e5 !important; }
        #studentCalendar .fc-event.ev-state { background-color:#0e7490 !important; border-color:#0e7490 !important; }
        @foreach ($branches as $b)
        #studentCalendar .fc-event.ev-branch-{{ $b->id }} { background-color:{{ $b->color ?: '#16a34a' }} !important; border-color:{{ $b->color ?: '#16a34a' }} !important; }
        @endforeach
        #studentCalendar .fc-event.ev-session-cancelled { background-color:#dc2626 !important; border-color:#dc2626 !important; }
        #studentCalendar .fc-event.ev-session-center_break { background-color:#94a3b8 !important; border-color:#94a3b8 !important; }
        #studentCalendar .fc-event.ev-session-cancelled .fc-event-title { text-decoration: line-through; }
        #studentCalendar .fc-event, #studentCalendar .fc-event .fc-event-title { color:#fff !important; font-weight:600 !important; }
        .scal-legend { display:flex; flex-wrap:wrap; gap:0.75rem 1.25rem; font-size:0.8rem; }
        .scal-legend span.dot { display:inline-block; width:0.7rem; height:0.7rem; border-radius:50%; margin-inline-end:0.35rem; vertical-align:middle; }
        .att-badge { display:inline-flex; align-items:center; justify-content:center; width:14px; height:14px; border-radius:50%; margin-inline-start:4px; font-size:9px; color:#fff; }
        .att-present { background:#15803d; }
        .att-late { background:#b45309; }
        .att-absent { background:#991b1b; }
        #studentCalendar .fc-view-harness { position: relative; }
        .cal-popover-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1085; border-radius: inherit; }
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

    <div class="scal-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h5 class="mb-0 fw-semibold">My Calendar</h5>
                <small class="text-muted">{{ $className ? $className.' — class schedule, holidays & events' : 'Holidays & events' }}</small>
            </div>
            <div class="scal-legend">
                <span><span class="dot" style="background:{{ $branchColor }}"></span>Class</span>
                <span><span class="dot" style="background:#dc2626"></span>Cancelled</span>
                <span><span class="dot" style="background:#94a3b8"></span>No class</span>
                <span><span class="dot" style="background:#4f46e5"></span>Holiday</span>
                <span class="att-badge att-present"><i class="ti ti-check"></i></span>Present
                <span class="att-badge att-late"><i class="ti ti-clock"></i></span>Late
                <span class="att-badge att-absent"><i class="ti ti-x"></i></span>Absent
            </div>
        </div>

        @unless ($hasClass)
            <div class="alert alert-info mb-3">You're not assigned to a class yet — showing public holidays and events only.</div>
        @endunless

        <div id="studentCalendar" data-events-url="{{ route('student.calendar.events') }}"></div>
    </div>
</div>
@endsection
