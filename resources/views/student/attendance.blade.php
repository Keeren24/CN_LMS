@extends('layouts/layoutMaster')

@section('title', 'Attendance')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss'])
@endsection

@section('page-style')
  <style>
    .fc-holiday-label {
      font-size: 0.68rem;
      color: #fff;
      font-weight: 600;
      padding: 2px 5px;
      margin: 2px 2px 0;
      background: #039BE5;
      border-radius: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .fc-remark-label {
      font-size: 0.6rem;
      color: #555;
      font-style: italic;
      padding: 0 2px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.js'])
@endsection

@section('content')
<div class="row g-4">

  {{-- Header --}}
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-4">
        <div class="avatar avatar-lg flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-success">
            <i class="ti ti-calendar-check ti-lg"></i>
          </span>
        </div>
        <div>
          <h5 class="mb-0">{{ $className ?: 'My Class' }}</h5>
          <small class="text-muted">Attendance Record</small>
          @if ($classInfo && $classInfo->class_day)
            <div class="text-muted small mt-1">
              <i class="ti ti-clock ti-xs me-1"></i>
              Scheduled: {{ $classInfo->class_day }}
              @if ($classInfo->start_time && $classInfo->end_time)
                &bull; {{ \Carbon\Carbon::parse($classInfo->start_time)->format('g:i A') }}
                – {{ \Carbon\Carbon::parse($classInfo->end_time)->format('g:i A') }}
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Summary Stats --}}
  @if ($totalSessions > 0)
    @php
      $pctColor = $attendancePct >= 90 ? 'success' : ($attendancePct >= 75 ? 'warning' : 'danger');
      $pctLabel = $attendancePct >= 90 ? 'Excellent' : ($attendancePct >= 75 ? 'Good' : 'Needs Attention');
    @endphp

    <div class="col-12">
      <div class="row g-3">
        <div class="col-12 col-sm-6 col-md-3">
          <div class="card border-0 shadow-sm h-100 text-center">
            <div class="card-body py-4">
              <div class="display-6 fw-bold text-{{ $pctColor }}">{{ $attendancePct }}%</div>
              <div class="text-muted small">Attendance Rate</div>
              <span class="badge bg-{{ $pctColor }} mt-2">{{ $pctLabel }}</span>
              <div class="progress mt-3" style="height:6px;">
                <div class="progress-bar bg-{{ $pctColor }}" style="width:{{ $attendancePct }}%;"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 bg-label-success h-100 text-center shadow-sm">
            <div class="card-body py-4">
              <i class="ti ti-circle-check ti-lg text-success mb-2 d-block"></i>
              <div class="fs-2 fw-bold text-success">{{ $presentCount }}</div>
              <div class="text-muted small">Present</div>
              <div class="text-muted small">out of {{ $totalSessions }} sessions</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 bg-label-danger h-100 text-center shadow-sm">
            <div class="card-body py-4">
              <i class="ti ti-circle-x ti-lg text-danger mb-2 d-block"></i>
              <div class="fs-2 fw-bold text-danger">{{ $absentCount }}</div>
              <div class="text-muted small">Absent</div>
              @if ($absentCount > 0)
                <div class="text-muted small">{{ round($absentCount / $totalSessions * 100, 0) }}% of sessions</div>
              @endif
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 bg-label-warning h-100 text-center shadow-sm">
            <div class="card-body py-4">
              <i class="ti ti-clock ti-lg text-warning mb-2 d-block"></i>
              <div class="fs-2 fw-bold text-warning">{{ $lateCount }}</div>
              <div class="text-muted small">Late</div>
              @if ($lateCount > 0)
                <div class="text-muted small">{{ round($lateCount / $totalSessions * 100, 0) }}% of sessions</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    @if ($recentMissed->isNotEmpty())
      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header border-0 pb-0">
            <h6 class="card-title mb-0">
              <i class="ti ti-alert-triangle text-warning me-2"></i>Recent Absences / Lates
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
                    <div class="small fw-semibold">
                      {{ \Carbon\Carbon::parse($date)->format('d M Y (D)') }}
                    </div>
                    @if ($rec['remarks'])
                      <div class="small text-muted">{{ $rec['remarks'] }}</div>
                    @endif
                  </div>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-8">
    @else
      <div class="col-12">
    @endif

  @else
    <div class="col-12">
  @endif

    {{-- Calendar --}}
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex gap-3 mb-3 flex-wrap">
          <span class="d-flex align-items-center gap-1">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#28c76f;"></span>
            <small>Present</small>
          </span>
          <span class="d-flex align-items-center gap-1">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#ea5455;"></span>
            <small>Absent</small>
          </span>
          <span class="d-flex align-items-center gap-1">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#ff9f43;"></span>
            <small>Late</small>
          </span>
          <span class="d-flex align-items-center gap-1">
            <span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#039BE5;"></span>
            <small>Public Holiday</small>
          </span>
        </div>
        <div id="attendanceCalendar"></div>
      </div>
    </div>
  </div>

</div>

<script>
  const records  = @json($records);
  const holidays = @json($holidays);

  const STATUS_COLORS = {
    present: '#28c76f',
    absent:  '#ea5455',
    late:    '#ff9f43',
  };

  const events = [];

  Object.entries(records).forEach(([date, rec]) => {
    events.push({
      start: date, allDay: true, display: 'background',
      color: STATUS_COLORS[rec.status] || '#e0e0e0',
      extendedProps: { remarks: rec.remarks || '', status: rec.status },
    });
  });

  Object.entries(holidays || {}).forEach(([date, name]) => {
    if (!records[date]) {
      events.push({
        start: date, allDay: true, display: 'background',
        color: '#039BE5',
        extendedProps: { holidayName: name, type: 'holiday' },
      });
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    const calendar = new Calendar(document.getElementById('attendanceCalendar'), {
      plugins:       [dayGridPlugin],
      initialView:   'dayGridMonth',
      headerToolbar: { start: 'prev', center: 'title', end: 'next' },
      events:        events,
      height:        'auto',
      dayCellDidMount: function (info) {
        const dateStr = info.date.getFullYear() + '-' +
          String(info.date.getMonth() + 1).padStart(2, '0') + '-' +
          String(info.date.getDate()).padStart(2, '0');

        const rec     = records[dateStr];
        const holiday = (holidays || {})[dateStr];
        const frame   = info.el.querySelector('.fc-daygrid-day-frame');

        if (holiday) {
          const el = document.createElement('div');
          el.className = 'fc-holiday-label';
          el.textContent = holiday.length > 15 ? holiday.substring(0, 15) + '…' : holiday;
          el.title = holiday;
          if (frame) frame.appendChild(el);
        }

        if (rec && rec.remarks) {
          const el = document.createElement('div');
          el.className = 'fc-remark-label';
          el.textContent = rec.remarks.length > 20 ? rec.remarks.substring(0, 20) + '…' : rec.remarks;
          el.title = rec.remarks;
          if (frame) frame.appendChild(el);
        }
      },
    });
    calendar.render();
  });
</script>

@endsection
