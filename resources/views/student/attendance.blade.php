@extends('layouts/layoutMaster')

@section('title', 'My Attendance')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="avatar">
      <span class="avatar-initial rounded-circle bg-label-success">
        <i class="ti ti-calendar-check"></i>
      </span>
    </div>
    <div>
      <h4 class="mb-0">My Attendance</h4>
      <p class="text-muted small mb-0">
        {{ $classInfo->name ?? 'My Class' }}
        @if ($classInfo && $classInfo->class_day)
          &middot; {{ $classInfo->class_day }}
          @if ($classInfo->start_time && $classInfo->end_time)
            {{ \Carbon\Carbon::parse($classInfo->start_time)->format('g:i A') }}
            – {{ \Carbon\Carbon::parse($classInfo->end_time)->format('g:i A') }}
          @endif
        @endif
      </p>
    </div>
  </div>

  @if ($totalSessions === 0)
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-calendar-off text-muted d-block mb-2" style="font-size:2.5rem;"></i>
        <p class="text-muted mb-0">No attendance records found.</p>
      </div>
    </div>
  @else

    @php
      $pctColor = $attendancePct >= 90 ? 'success' : ($attendancePct >= 75 ? 'warning' : 'danger');
      $pctLabel = $attendancePct >= 90 ? 'Excellent' : ($attendancePct >= 75 ? 'Good' : 'Needs Attention');
    @endphp

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card text-center h-100">
          <div class="card-body py-4">
            <div class="display-6 fw-bold text-{{ $pctColor }}">{{ $attendancePct }}%</div>
            <div class="text-muted small">Attendance Rate</div>
            <span class="badge bg-{{ $pctColor }} mt-2">{{ $pctLabel }}</span>
            <div class="progress mt-3" style="height:5px;">
              <div class="progress-bar bg-{{ $pctColor }}" style="width:{{ $attendancePct }}%;"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-label-success text-center h-100">
          <div class="card-body py-4">
            <i class="ti ti-circle-check ti-lg text-success d-block mb-1"></i>
            <div class="fs-2 fw-bold text-success">{{ $presentCount }}</div>
            <div class="text-muted small">Present</div>
            <div class="text-muted small">of {{ $totalSessions }} sessions</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-label-danger text-center h-100">
          <div class="card-body py-4">
            <i class="ti ti-circle-x ti-lg text-danger d-block mb-1"></i>
            <div class="fs-2 fw-bold text-danger">{{ $absentCount }}</div>
            <div class="text-muted small">Absent</div>
            @if ($absentCount > 0)
              <div class="text-muted small">{{ round($absentCount / $totalSessions * 100) }}% of sessions</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card bg-label-warning text-center h-100">
          <div class="card-body py-4">
            <i class="ti ti-clock ti-lg text-warning d-block mb-1"></i>
            <div class="fs-2 fw-bold text-warning">{{ $lateCount }}</div>
            <div class="text-muted small">Late</div>
            @if ($lateCount > 0)
              <div class="text-muted small">{{ round($lateCount / $totalSessions * 100) }}% of sessions</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">

      {{-- Recent absences / lates --}}
      @if ($recentMissed->isNotEmpty())
        <div class="col-12 col-md-4">
          <div class="card h-100">
            <div class="card-header pb-0">
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

        {{-- Calendar --}}
        <div class="card">
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
            </div>
            <div id="attendanceCalendar"></div>
          </div>
        </div>
      </div>

    </div>

  @endif

</div>

<script>
  const records = @json($records);

  const STATUS_COLORS = {
    present: '#28c76f',
    absent:  '#ea5455',
    late:    '#ff9f43',
  };

  const events = Object.entries(records).map(([date, rec]) => ({
    start:   date,
    allDay:  true,
    display: 'background',
    color:   STATUS_COLORS[rec.status] || '#e0e0e0',
    extendedProps: { remarks: rec.remarks || '', status: rec.status },
  }));

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

        const rec   = records[dateStr];
        const frame = info.el.querySelector('.fc-daygrid-day-frame');

        if (rec && rec.remarks && frame) {
          const el = document.createElement('div');
          el.style.cssText = 'font-size:.6rem;color:#555;font-style:italic;padding:0 2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
          el.textContent = rec.remarks.length > 20 ? rec.remarks.substring(0, 20) + '…' : rec.remarks;
          el.title = rec.remarks;
          frame.appendChild(el);
        }
      },
    });
    calendar.render();
  });
</script>

@endsection
