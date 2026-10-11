@extends('layouts/layoutMaster')

@section('title', 'AI Tutor')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="avatar">
      <span class="avatar-initial rounded-circle bg-label-primary">
        <i class="ti ti-message-chatbot"></i>
      </span>
    </div>
    <div>
      <h4 class="mb-0">AI Tutor</h4>
      <p class="text-muted small mb-0">Stuck on something? Pick a subject and ask your tutor for help.</p>
    </div>
  </div>

  @if ($courses->isEmpty())
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-books-off text-muted d-block mb-2" style="font-size:2.5rem;"></i>
        <p class="text-muted mb-0">No subjects yet. They show up here once homework is set for your class.</p>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach ($courses as $course)
        <div class="col-md-6 col-xl-4">
          <a href="{{ route('student.ai-tutor.show', $course) }}" class="card h-100 text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="avatar avatar-lg flex-shrink-0">
                <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-book"></i></span>
              </div>
              <div class="flex-grow-1 min-w-0">
                <h6 class="mb-1 text-heading text-truncate">{{ $course->name }}</h6>
                @if ($course->level)
                  <span class="badge bg-label-secondary">{{ $course->level }}</span>
                @endif
              </div>
              <i class="ti ti-chevron-right text-muted"></i>
            </div>
          </a>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection
