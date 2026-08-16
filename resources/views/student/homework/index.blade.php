@extends('layouts/layoutMaster')

@section('title', 'My Homework & Quizzes')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="mb-4">My Homework &amp; Quizzes</h4>

  @if (session('info'))
    <div class="alert alert-info alert-dismissible mb-4" role="alert">
      {{ session('info') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if ($homeworks->isEmpty())
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-book-off ti-lg text-muted mb-2 d-block" style="font-size: 2.5rem;"></i>
        <p class="text-muted mb-0">No homework or quizzes assigned to your class yet.</p>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach ($homeworks as $hw)
        @php
          $latest    = $hw->submissions->first();
          $possible  = $hw->questions_sum_marks ?? $hw->questions->sum('marks');
          $best      = $hw->submissions->max('final_total_marks');
          $threshold = $hw->pass_threshold ?? 70;
          $pct       = ($possible > 0 && $best !== null) ? round($best / $possible * 100, 1) : null;
          $passed    = $pct !== null && $pct >= $threshold;

          [$badgeClass, $statusLabel] = match(true) {
            $passed                                          => ['bg-success',   'Passed'],
            $latest && $latest->marking_status === 'finalised' && !$passed
                                                             => ['bg-danger',    'Failed'],
            $latest && $latest->marking_status !== 'finalised'
                                                             => ['bg-warning',   'Awaiting Mark'],
            default                                          => ['bg-secondary', 'Not Attempted'],
          };
        @endphp

        <div class="col-12 col-md-6 col-xl-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                <span class="text-muted small">{{ $possible }} marks</span>
              </div>

              <h6 class="mb-1">{{ $hw->title }}</h6>
              <p class="text-muted small mb-3">
                {{ $hw->course->name ?? '—' }} &middot; {{ $hw->questions->count() }} question(s)
              </p>

              @if ($pct !== null)
                <div class="mb-3">
                  <div class="d-flex justify-content-between small mb-1">
                    <span>Best score</span>
                    <span>{{ $best }} / {{ $possible }} ({{ $pct }}%)</span>
                  </div>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar {{ $passed ? 'bg-success' : 'bg-danger' }}"
                         style="width: {{ $pct }}%;"></div>
                  </div>
                </div>
              @endif
            </div>

            <div class="card-footer bg-transparent">
              @if ($latest && $latest->marking_status !== 'finalised')
                <a href="{{ route('student.homework.submission.view', $latest->id) }}"
                   class="btn btn-sm btn-outline-info w-100">View Submission</a>
              @elseif ($passed)
                <a href="{{ route('student.homework.submission.view', $latest->id) }}"
                   class="btn btn-sm btn-outline-success w-100">View Result</a>
              @else
                <a href="{{ route('student.homework.view', $hw->id) }}"
                   class="btn btn-sm btn-primary w-100">
                  {{ $latest ? 'Retry' : 'Start' }}
                </a>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection
