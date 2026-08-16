@extends('layouts/layoutMaster')

@section('title', 'My Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')

    {{-- ── Welcome Banner ────────────────────────────────────────────────────── --}}
    <div class="card border-0 mb-4 overflow-hidden position-relative"
         style="background: linear-gradient(135deg, #0093E9 0%, #00bf8f 100%); min-height: 140px;">

        {{-- Decorative circles --}}
        <div class="position-absolute top-0 end-0"
             style="width:280px;height:280px;border-radius:50%;
                    background:rgba(255,255,255,.08);
                    transform:translate(75%,-75%);
                    pointer-events:none;z-index:0;"></div>
        <div class="position-absolute bottom-0 start-0"
             style="width:180px;height:180px;border-radius:50%;
                    background:rgba(255,255,255,.08);
                    transform:translate(-75%,75%);
                    pointer-events:none;z-index:0;"></div>

        <div class="card-body d-flex flex-column flex-md-row
                    align-items-start align-items-md-center justify-content-between gap-3 py-4 px-4"
             style="position:relative;z-index:1;">
            <div>
                <div class="text-white opacity-75 small mb-1">
                    {{ now()->format('l, d F Y') }}
                    @if ($className)
                        &bull; <i class="ti ti-building-community ti-xs me-1"></i>{{ $className }}
                    @endif
                </div>
                <h3 class="text-white fw-bold mb-1">{{ $greeting }}, {{ $firstName }}! 👋</h3>
                <p class="text-white opacity-75 mb-0 small">
                    @if ($totalHw === 0)
                        No homeworks assigned yet — check back soon!
                    @elseif (($totalHw - $submitted) > 0)
                        You have <strong class="text-white">{{ $totalHw - $submitted }}</strong>
                        homework{{ ($totalHw - $submitted) > 1 ? 's' : '' }} waiting to be started.
                    @elseif ($pending > 0)
                        <strong class="text-white">{{ $pending }}</strong>
                        submission{{ $pending > 1 ? 's' : '' }} currently under review.
                    @else
                        All submissions in — great effort! Keep it up 🎉
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('student.attendance') }}" class="btn btn-light btn-sm fw-semibold">
                    <i class="ti ti-calendar-check me-1"></i>Attendance
                </a>
                <a href="#subjectAccordion" class="btn btn-outline-light btn-sm text-white">
                    <i class="ti ti-list me-1"></i>Homeworks
                </a>
            </div>
        </div>
    </div>

    {{-- ── Quick Stats ─────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded-circle bg-label-secondary">
                            <i class="ti ti-clipboard-list"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 lh-1">{{ $totalHw }}</div>
                        <div class="text-muted small">Assigned</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded-circle bg-label-info">
                            <i class="ti ti-send"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 lh-1 text-info">{{ $submitted }}</div>
                        <div class="text-muted small">Submitted</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded-circle bg-label-success">
                            <i class="ti ti-trophy"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 lh-1 text-success">{{ $passed }}</div>
                        <div class="text-muted small">Passed</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $pending > 0 ? 'border border-warning' : '' }}">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded-circle {{ $pending > 0 ? 'bg-warning' : 'bg-label-warning' }}">
                            <i class="ti ti-clock {{ $pending > 0 ? 'text-white' : '' }}"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 lh-1 {{ $pending > 0 ? 'text-warning' : '' }}">{{ $pending }}</div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Points — full-width gradient card --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden position-relative"
                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="position-absolute end-0 top-50"
                     style="width:160px;height:160px;border-radius:50%;
                            background:rgba(255,255,255,.07);
                            transform:translate(40%,-50%);pointer-events:none;"></div>
                <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between gap-3"
                     style="position:relative;z-index:1;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md flex-shrink-0">
                            <span class="avatar-initial rounded-circle" style="background:rgba(255,255,255,.2);">
                                <i class="ti ti-star text-white ti-md"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-white opacity-75 small mb-0">Total Class Points Earned</div>
                            <div class="text-white fw-bold" style="font-size:1.6rem;line-height:1.1;">
                                {{ number_format($totalPoints) }}
                                <span class="opacity-75 fw-normal" style="font-size:.8rem;">pts</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="text-white opacity-60 small">Awarded by your tutor</div>
                        <a href="{{ route('student.class-points') }}" class="text-white opacity-75 small text-decoration-underline">
                            <i class="ti ti-trophy ti-xs me-1"></i>View Leaderboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Homework by Subject (Accordion) ─────────────────────────────────── --}}
    @if ($homeworks->isEmpty())
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="ti ti-clipboard-off ti-48px mb-3 d-block"></i>
                No homework assigned yet. Check back soon!
            </div>
        </div>
    @else
        @php $grouped = $homeworks->groupBy('course_id'); @endphp
        <div class="accordion mb-4" id="subjectAccordion">
            @foreach ($grouped as $courseId => $courseHws)
                @php
                    $subject       = $courseHws->first()->course;
                    $courseName    = $subject->name ?? 'Unknown';
                    $subTotal      = $subjectTotals->firstWhere('course', $courseName) ?? [];
                    $sAchieved     = $subTotal['achieved'] ?? 0;
                    $sPossible     = $subTotal['possible'] ?? 0;
                    $sPct          = $subTotal['pct'] ?? 0;
                    $barColor      = $sPct >= 70 ? 'bg-success' : ($sPct >= 50 ? 'bg-warning' : 'bg-danger');
                    $notStarted    = $courseHws->filter(fn($hw) => $hw->submissions->isEmpty())->count();
                    $subjectPassed = $courseHws->filter(function ($hw) {
                        $possible  = $hw->questions_sum_marks ?? $hw->questions->sum('marks');
                        $best      = $hw->submissions->max('final_total_marks');
                        $threshold = $hw->pass_threshold ?? 70;
                        return $possible > 0 && $best !== null && ($best / $possible * 100) >= $threshold;
                    })->count();
                @endphp

                <div class="accordion-item border rounded mb-3 shadow-sm" id="subject-{{ $courseId }}">
                    <h2 class="accordion-header" id="hdr-{{ $courseId }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#col-{{ $courseId }}"
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                            <div class="flex-grow-1 me-3">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                    <i class="ti ti-book-2 text-primary"></i>
                                    <span class="fw-bold">{{ $courseName }}</span>
                                    <span class="badge bg-label-secondary">{{ $courseHws->count() }} hw</span>
                                    <span class="badge bg-success">{{ $subjectPassed }} passed</span>
                                    @if ($notStarted > 0)
                                        <span class="badge bg-warning text-dark">{{ $notStarted }} to do</span>
                                    @endif
                                </div>
                                @if ($sPossible > 0)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:5px;max-width:200px;">
                                            <div class="progress-bar {{ $barColor }}" style="width:{{ min($sPct, 100) }}%"></div>
                                        </div>
                                        <small class="text-muted text-nowrap">
                                            {{ $sAchieved }}/{{ $sPossible }} pts &bull; {{ $sPct }}%
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </button>
                    </h2>

                    <div id="col-{{ $courseId }}"
                         class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                         data-bs-parent="#subjectAccordion">
                        <div class="accordion-body p-0">
                            @foreach ($courseHws as $hw)
                                @php
                                    $latestSub   = $hw->submissions->first();
                                    $bestMarks   = $hw->submissions->max('final_total_marks');
                                    $hwPossible  = $hw->questions_sum_marks ?? $hw->questions->sum('marks');
                                    $threshold   = $hw->pass_threshold ?? 70;
                                    $hwPassed    = $hwPossible > 0 && $bestMarks !== null
                                                    && ($bestMarks / $hwPossible * 100) >= $threshold;
                                    $canRetry    = $latestSub && ! $hwPassed
                                                    && $latestSub->marking_status === 'finalised';
                                    $isFinalised = $latestSub && $latestSub->marking_status === 'finalised';
                                @endphp

                                <div class="d-flex align-items-center justify-content-between
                                            px-3 px-md-4 py-3 border-top">
                                    <div class="flex-grow-1 me-3 min-width-0">
                                        <div class="fw-semibold text-truncate mb-1">{{ $hw->title }}</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            @if (! $latestSub)
                                                <span class="badge bg-warning text-dark">Not started</span>
                                            @elseif ($hwPassed)
                                                <span class="badge bg-success">Passed ✓</span>
                                                <span class="text-muted small">{{ $bestMarks }}/{{ $hwPossible }} pts</span>
                                            @elseif ($isFinalised)
                                                <span class="badge bg-danger">
                                                    {{ round($bestMarks / $hwPossible * 100, 0) }}% — below {{ $threshold }}%
                                                </span>
                                                <span class="text-muted small">{{ $bestMarks }}/{{ $hwPossible }} pts</span>
                                            @else
                                                <span class="badge bg-info">Pending marking</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 flex-shrink-0">
                                        @if ($latestSub)
                                            @if ($isFinalised)
                                                <a href="{{ route('student.homework.submission.view', $latestSub->id) }}"
                                                   class="btn btn-sm btn-outline-primary">Result</a>
                                            @else
                                                <a href="{{ route('student.homework.submission.view', $latestSub->id) }}"
                                                   class="btn btn-sm btn-outline-info">Status</a>
                                            @endif
                                            @if ($canRetry)
                                                <a href="{{ route('student.homework.view', $hw->id) }}"
                                                   class="btn btn-sm btn-warning">Retry</a>
                                            @endif
                                        @else
                                            <a href="{{ route('student.homework.view', $hw->id) }}"
                                               class="btn btn-sm btn-primary">Start</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Class Points History Chart ───────────────────────────────────────── --}}
    @if ($classPoints->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 pb-0">
                <h6 class="card-title mb-0">
                    <i class="ti ti-chart-area me-2 text-primary"></i>Class Points History
                </h6>
            </div>
            <div class="card-body">
                <div id="classPointsChart"></div>
            </div>
        </div>
    @endif

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    @if ($classPoints->isNotEmpty())
    const classPointsData = @json($classPoints);
    const dates   = classPointsData.map(r => r.date);
    const points  = classPointsData.map(r => r.points);
    const remarks = classPointsData.map(r => r.remarks || '');

    new ApexCharts(document.querySelector('#classPointsChart'), {
        chart: { type: 'area', height: 280, toolbar: { show: false } },
        series: [{ name: 'Points', data: points }],
        xaxis: {
            categories: dates,
            labels: {
                formatter: val => val
                    ? new Date(val).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
                    : val
            }
        },
        yaxis: { labels: { formatter: val => Math.round(val) } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        colors: ['#7367f0'],
        tooltip: {
            custom({ series, seriesIndex, dataPointIndex }) {
                const pts    = series[seriesIndex][dataPointIndex];
                const date   = dates[dataPointIndex];
                const remark = remarks[dataPointIndex];
                return '<div class="px-3 py-2">' +
                    '<div class="fw-bold">' + date + '</div>' +
                    '<div>Points: <strong>' + pts + '</strong></div>' +
                    (remark ? '<div class="text-muted small">' + remark + '</div>' : '') +
                    '</div>';
            }
        },
        markers: { size: 4 },
        grid: { borderColor: '#f0f0f0' }
    }).render();
    @endif

});
</script>
@endsection
