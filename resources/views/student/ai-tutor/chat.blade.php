@extends('layouts/layoutMaster')

@section('title', 'AI Tutor — ' . $course->name)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
  <style>
    .tutor-messages { height: 55vh; min-height: 320px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; }
    .tutor-bubble { max-width: 85%; padding: 0.6rem 0.9rem; border-radius: 0.9rem; white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.45; }
    .tutor-bubble.is-user { align-self: flex-end; background: var(--bs-primary); color: #fff; border-bottom-right-radius: 0.25rem; }
    .tutor-bubble.is-assistant { align-self: flex-start; background: var(--bs-gray-200, #e9ecef); color: var(--bs-body-color); border-bottom-left-radius: 0.25rem; }
    .tutor-bubble.is-typing { opacity: 0.7; font-style: italic; }
    .tutor-bubble.is-rendered { white-space: normal; min-width: 0; }
    .tutor-bubble.is-rendered p { white-space: pre-wrap; margin: 0 0 0.5rem; }
    .tutor-bubble.is-rendered ul, .tutor-bubble.is-rendered ol { margin: 0 0 0.5rem; padding-left: 1.25rem; }
    .tutor-bubble.is-rendered li { margin-bottom: 0.2rem; }
    .tutor-bubble.is-rendered code { background: rgba(0, 0, 0, 0.09); padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.9em; }
    .tutor-bubble.is-rendered pre { background: #1e1e2e; color: #f8f8f2; padding: 0.6rem 0.8rem; border-radius: 0.5rem; margin: 0 0 0.5rem; overflow-x: auto; white-space: pre; }
    .tutor-bubble.is-rendered pre code { background: none; color: inherit; padding: 0; font-size: 0.85em; }
    .tutor-bubble.is-rendered > :last-child { margin-bottom: 0; }
    .dark-style .tutor-bubble.is-assistant { background: rgba(255, 255, 255, 0.08); }
    .tutor-empty { margin: auto; text-align: center; color: var(--bs-secondary-color); }
    @media (max-width: 575.98px) {
      .tutor-messages { padding: 0.75rem; }
      .tutor-bubble { max-width: 96%; }
    }
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/student-ai-tutor-chat.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div class="d-flex align-items-center gap-3">
      <a href="{{ route('student.ai-tutor.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left"></i>
      </a>
      <div>
        <h4 class="mb-0">{{ $course->name }}</h4>
        <p class="text-muted small mb-0">
          AI Tutor
          @if ($course->level) &middot; {{ $course->level }} @endif
        </p>
      </div>
    </div>
    <span class="badge bg-label-primary">
      <span id="remainingCount">{{ $remaining }}</span> / {{ $dailyLimit }} messages left today
    </span>
  </div>

  <div class="card"
       id="aiTutorChat"
       data-send-url="{{ route('student.ai-tutor.send', $course) }}"
       data-remaining="{{ $remaining }}">

    <div class="tutor-messages" id="chatMessages">
      @forelse ($history as $message)
        <div class="tutor-bubble {{ $message->role === 'user' ? 'is-user' : 'is-assistant' }}">{{ $message->content }}</div>
      @empty
        <div class="tutor-empty" id="chatEmpty">
          <i class="ti ti-message-chatbot d-block mb-2" style="font-size:2.5rem;"></i>
          <p class="mb-0">Hi! Ask me anything about {{ $course->name }}.<br>
            I'll give hints and explain ideas, but you'll do the solving.</p>
        </div>
      @endforelse
    </div>

    <div class="card-footer border-top">
      <div class="alert alert-warning mb-3 {{ $remaining > 0 ? 'd-none' : '' }}" id="limitAlert">
        You've used all of today's tutor messages. Come back tomorrow!
      </div>
      <form id="chatForm" class="d-flex gap-2 align-items-end">
        <textarea id="chatInput" class="form-control" rows="2" maxlength="2000"
                  placeholder="Type your question…" @disabled($remaining <= 0)></textarea>
        <button type="submit" id="chatSend" class="btn btn-primary" @disabled($remaining <= 0)>
          <i class="ti ti-send"></i>
        </button>
      </form>
      <div class="form-text mt-1">Enter to send, Shift+Enter for a new line.</div>
    </div>
  </div>

</div>
@endsection
