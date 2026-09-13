@extends('layouts/layoutMaster')

@section('title', 'My Profile')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="avatar avatar-lg">
            <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.2rem;">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </span>
        </div>
        <div>
            <h4 class="mb-0">{{ $user->name }}</h4>
            <span class="badge bg-label-primary">Student</span>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-4">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="mb-1">Your details</h6>
            <p class="text-muted small">These details are shown to your tutors.</p>

            <form method="POST" action="{{ route('profile.details') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Full name *</label>
                        <input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="phone_no">Phone number</label>
                        <input id="phone_no" name="phone_no" class="form-control" value="{{ old('phone_no', $user->phone_no) }}" placeholder="012-345 6789">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="email">Login</label>
                        <input id="email" class="form-control" value="{{ $user->email }}" disabled>
                        <div class="form-text">This is your school portal login. Ask your tutor or an academy admin to change it.</div>
                    </div>

                    @if ($student)
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <input class="form-control" value="{{ optional($student->class_info)->name ?? 'Not assigned' }}" disabled>
                            <div class="form-text">Your tutor manages this.</div>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save details</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="mb-1">Change your password</h6>
            <p class="text-muted small">Use at least 8 characters. You will stay signed in on this device.</p>

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label" for="current_password">Current password *</label>
                        <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">New password *</label>
                        <input id="password" name="password" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Confirm new password *</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary"><i class="ti ti-lock me-1"></i>Change password</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
