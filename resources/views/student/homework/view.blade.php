@extends('layouts/layoutMaster')

@section('title', $homework->title)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h4 class="mb-0">{{ $homework->title }}</h4>
      <p class="text-muted small mb-0">
        {{ $homework->questions->count() }} question(s) &middot;
        {{ $homework->questions->sum('marks') }} total marks
      </p>
    </div>
    <a href="{{ route('student.homework.index') }}" class="btn btn-sm btn-outline-secondary">
      ← Back
    </a>
  </div>

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible mb-4">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if ($latestSubmission)
    <div class="alert alert-info mb-4">
      This is attempt #{{ $latestSubmission->attempt_number + 1 }}.
      Your previous attempt scored {{ $latestSubmission->final_total_marks }} marks.
    </div>
  @endif

  <form method="POST" action="{{ route('student.homework.submit', $homework->id) }}">
    @csrf

    @foreach ($homework->questions->sortBy('question_no') as $q)
      <div class="card mb-4">
        <div class="card-body">

          <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="mb-0">
              <span class="badge bg-secondary me-2">Q{{ $q->question_no }}</span>
              {{ $q->question_text }}
            </h6>
            <span class="badge bg-label-primary ms-2 flex-shrink-0">{{ $q->marks }} mark(s)</span>
          </div>

          @if ($q->image_path)
            <div class="mb-3">
              <img src="{{ asset($q->image_path) }}" alt="Question image"
                   class="img-fluid rounded" style="max-width: 420px;">
            </div>
          @endif

          @if ($q->question_type === 'mcq')
            <div class="mt-2">
              @foreach ($q->options as $opt)
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio"
                         name="answers[{{ $q->id }}]" value="{{ $opt->id }}"
                         id="opt_{{ $opt->id }}">
                  <label class="form-check-label" for="opt_{{ $opt->id }}">
                    <strong>{{ $opt->option_label }}.</strong> {{ $opt->option_text }}
                  </label>
                </div>
              @endforeach
            </div>
          @endif

          @if ($q->question_type === 'subjective')
            <textarea class="form-control mt-2" name="answers[{{ $q->id }}]"
                      rows="4" placeholder="Type your answer here..."></textarea>
          @endif

        </div>
      </div>
    @endforeach

    <div class="d-flex justify-content-end gap-2 mb-4">
      <a href="{{ route('student.homework.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-success">
        <i class="ti ti-send me-1"></i> Submit
      </button>
    </div>

  </form>

</div>
@endsection
