@extends('layouts/layoutMaster')

@section('title', 'Submission — ' . $submission->homework->title)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <p class="text-muted small mb-0">{{ $finalised ? 'Your Result' : 'Submission Status' }}</p>
      <h4 class="mb-0">{{ $submission->homework->title }}</h4>
    </div>
    <a href="{{ route('student.homework.index') }}" class="btn btn-sm btn-outline-secondary">
      ← Back to Homework
    </a>
  </div>

  @if (session('submitted'))
    <div class="alert alert-success alert-dismissible mb-4">
      Homework submitted successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if (! $finalised)

    {{-- Pending --}}
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="avatar avatar-xl mx-auto mb-3">
          <span class="avatar-initial rounded-circle bg-label-info" style="width:60px;height:60px;">
            <i class="ti ti-clock" style="font-size:1.8rem;"></i>
          </span>
        </div>
        <h5 class="mb-1">Your submission is being reviewed</h5>
        <p class="text-muted mb-3">Results will appear here once your tutor has finished marking.</p>
        @php
          $statusLabel = match($submission->marking_status) {
            'submitted'     => 'Submitted — awaiting marking',
            'auto_checked'  => 'Auto-checked — awaiting tutor review',
            'tutor_checked' => 'Tutor review in progress',
            default         => ucfirst(str_replace('_', ' ', $submission->marking_status)),
          };
        @endphp
        <span class="badge bg-info fs-6 px-3 py-2">{{ $statusLabel }}</span>
      </div>
    </div>

  @else

    {{-- Score summary --}}
    @php
      $totalPossible = $submission->answers->sum(fn($a) => $a->question->marks ?? 0);
      $finalTotal    = $submission->final_total_marks ?? 0;
      $pct           = $totalPossible > 0 ? round($finalTotal / $totalPossible * 100, 1) : 0;
      $threshold     = $submission->homework->pass_threshold ?? 70;
      $passed        = $pct >= $threshold;
    @endphp

    <div class="card {{ $passed ? 'border-success' : 'border-danger' }} mb-4">
      <div class="card-body py-3">
        <div class="row align-items-center g-3">
          <div class="col-auto">
            <div class="avatar avatar-xl">
              <span class="avatar-initial rounded-circle {{ $passed ? 'bg-success' : 'bg-danger' }}"
                    style="width:56px;height:56px;">
                <i class="ti {{ $passed ? 'ti-trophy' : 'ti-mood-sad' }}"
                   style="font-size:1.5rem;color:#fff;"></i>
              </span>
            </div>
          </div>
          <div class="col">
            <h4 class="mb-0 {{ $passed ? 'text-success' : 'text-danger' }}">
              {{ $finalTotal }} / {{ $totalPossible }}
              <small class="fs-5">({{ $pct }}%)</small>
            </h4>
            <p class="text-muted small mb-1">
              {{ $passed ? 'Passed — Well done!' : 'Below pass threshold (' . $threshold . '%)' }}
            </p>
            <div class="progress" style="height: 6px; max-width: 300px;">
              <div class="progress-bar {{ $passed ? 'bg-success' : 'bg-danger' }}"
                   style="width: {{ $pct }}%;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Questions & answers --}}
    @foreach ($submission->answers->sortBy('question.question_no') as $ans)
      @php
        $question   = $ans->question;
        $yourAnswer = null;
        $correct    = null;
        if ($question->question_type === 'mcq') {
          foreach ($question->options as $opt) {
            if ($opt->id == $ans->selected_option_id) $yourAnswer = $opt;
            if ($opt->is_correct) $correct = $opt;
          }
        }
      @endphp

      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-0">
              <span class="badge bg-secondary me-2">Q{{ $question->question_no }}</span>
              {{ $question->question_text }}
            </h6>
            <span class="badge ms-2 flex-shrink-0 {{ $ans->final_marks > 0 ? 'bg-success' : 'bg-danger' }}">
              {{ $ans->final_marks ?? 0 }} / {{ $question->marks }} pts
            </span>
          </div>

          @if ($question->question_type === 'mcq')
            <div class="list-group">
              @foreach ($question->options as $opt)
                @php
                  $isSelected = $opt->id == $ans->selected_option_id;
                  $isCorrect  = $opt->is_correct;
                  $cls        = $isCorrect ? 'list-group-item-success' : ($isSelected && !$isCorrect ? 'list-group-item-danger' : '');
                @endphp
                <div class="list-group-item {{ $cls }} d-flex align-items-center gap-2">
                  @if ($isCorrect)
                    <i class="ti ti-check text-success"></i>
                  @elseif ($isSelected)
                    <i class="ti ti-x text-danger"></i>
                  @else
                    <i class="ti ti-minus text-muted"></i>
                  @endif
                  <span><strong>{{ $opt->option_label }}.</strong> {{ $opt->option_text }}</span>
                  @if ($isSelected)
                    <span class="badge bg-secondary ms-auto">Your answer</span>
                  @endif
                </div>
              @endforeach
            </div>
          @endif

          @if ($question->question_type === 'subjective')
            <div class="mt-2">
              <p class="text-muted small fw-semibold mb-1">Your Answer</p>
              <div class="border rounded p-2 bg-light">{{ $ans->answer_text ?? '—' }}</div>
            </div>
          @endif

        </div>
      </div>
    @endforeach

  @endif

</div>
@endsection
