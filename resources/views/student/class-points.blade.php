@extends('layouts/layoutMaster')

@section('title', 'Class Leaderboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header --}}
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="avatar">
      <span class="avatar-initial rounded-circle bg-label-warning">
        <i class="ti ti-trophy"></i>
      </span>
    </div>
    <div>
      <h4 class="mb-0">Class Leaderboard</h4>
      <p class="text-muted small mb-0">{{ $classInfo->name ?? 'My Class' }}</p>
    </div>
  </div>

  @if ($rankings->isEmpty())
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="ti ti-trophy-off text-muted d-block mb-2" style="font-size:2.5rem;"></i>
        <p class="text-muted mb-0">No points recorded yet for this class.</p>
      </div>
    </div>
  @else

    {{-- My position banner --}}
    @if ($myRank)
      @php
        $bannerColor = $myRank === 1 ? 'warning' : ($myRank <= 3 ? 'info' : 'secondary');
      @endphp
      <div class="alert alert-{{ $bannerColor }} d-flex align-items-center gap-3 mb-4">
        <i class="ti ti-medal ti-lg"></i>
        <div>
          You are ranked <strong>#{{ $myRank }}</strong> out of {{ $totalStudents }} students
          with <strong>{{ $myPoints }} points</strong>.
          @if ($myRank === 1) Keep it up — you're #1! 🏆
          @elseif ($myRank <= 3) Great work — you're in the top 3!
          @endif
        </div>
      </div>
    @endif

    {{-- Top 3 podium --}}
    @if ($rankings->count() >= 3)
      <div class="row g-3 mb-4 justify-content-center">
        {{-- 2nd --}}
        <div class="col-4 col-md-3 text-center">
          <div class="card {{ $rankings[1]->student_id == $student_id ? 'border-primary' : '' }} h-100">
            <div class="card-body py-3">
              <div class="avatar avatar-lg mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-secondary" style="font-size:1.2rem;">2</span>
              </div>
              <p class="mb-0 fw-semibold small text-truncate">{{ $rankings[1]->student_name }}</p>
              <p class="text-muted small mb-0">{{ $rankings[1]->total_points }} pts</p>
            </div>
          </div>
        </div>
        {{-- 1st --}}
        <div class="col-4 col-md-3 text-center">
          <div class="card border-warning {{ $rankings[0]->student_id == $student_id ? 'border-primary' : '' }} h-100">
            <div class="card-body py-3">
              <i class="ti ti-crown text-warning d-block mb-1"></i>
              <div class="avatar avatar-xl mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-warning text-white" style="font-size:1.4rem;">1</span>
              </div>
              <p class="mb-0 fw-bold small text-truncate">{{ $rankings[0]->student_name }}</p>
              <p class="text-warning fw-bold small mb-0">{{ $rankings[0]->total_points }} pts</p>
            </div>
          </div>
        </div>
        {{-- 3rd --}}
        <div class="col-4 col-md-3 text-center">
          <div class="card {{ $rankings[2]->student_id == $student_id ? 'border-primary' : '' }} h-100">
            <div class="card-body py-3">
              <div class="avatar avatar-lg mx-auto mb-2">
                <span class="avatar-initial rounded-circle bg-label-secondary" style="font-size:1.2rem;">3</span>
              </div>
              <p class="mb-0 fw-semibold small text-truncate">{{ $rankings[2]->student_name }}</p>
              <p class="text-muted small mb-0">{{ $rankings[2]->total_points }} pts</p>
            </div>
          </div>
        </div>
      </div>
    @endif

    {{-- Full ranking table --}}
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Full Rankings</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">Rank</th>
              <th>Student</th>
              <th class="text-center">Sessions</th>
              <th class="text-end">Total Points</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rankings as $row)
              <tr class="{{ $row->student_id == $student_id ? 'table-primary fw-semibold' : '' }}">
                <td>
                  @if ($row->rank === 1)
                    <span class="badge bg-warning text-dark">🥇 1</span>
                  @elseif ($row->rank === 2)
                    <span class="badge bg-secondary">🥈 2</span>
                  @elseif ($row->rank === 3)
                    <span class="badge bg-label-warning">🥉 3</span>
                  @else
                    <span class="text-muted">#{{ $row->rank }}</span>
                  @endif
                </td>
                <td>
                  {{ $row->student_name }}
                  @if ($row->student_id == $student_id)
                    <span class="badge bg-label-primary ms-1">You</span>
                  @endif
                </td>
                <td class="text-center text-muted small">{{ $row->session_count }}</td>
                <td class="text-end fw-bold {{ $row->rank === 1 ? 'text-warning' : '' }}">
                  {{ $row->total_points }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

  @endif
</div>
@endsection
