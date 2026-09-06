@extends('layouts/layoutMaster')

@section('title', 'Code Editor')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/student-ide-index.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Code Editor</h4>
            <p class="text-muted mb-0">Build and run HTML/CSS/JS and Python projects, right in your browser.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal"
                @disabled($projects->count() >= $maxProjects)>
            <i class="ti ti-plus me-1"></i>New Project
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($projects->count() >= $maxProjects)
        <div class="alert alert-warning mb-4">
            You've reached the maximum of {{ $maxProjects }} projects. Delete one to create a new project.
        </div>
    @endif

    @if ($projects->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="ti ti-code-dots ti-lg text-muted mb-3" style="font-size:3rem;"></i>
                <h5>No projects yet</h5>
                <p class="text-muted">Create your first project to start coding.</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newProjectModal">
                    <i class="ti ti-plus me-1"></i>New Project
                </button>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach ($projects as $project)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge {{ $project->kind === 'python' ? 'bg-label-info' : 'bg-label-warning' }}">
                                    <i class="ti {{ $project->kind === 'python' ? 'ti-brand-python' : 'ti-brand-html5' }} ti-xs me-1"></i>
                                    {{ $project->kind === 'python' ? 'Python' : 'Web' }}
                                </span>
                                @if ($project->package_set && $project->package_set !== 'basic')
                                    <span class="badge bg-label-secondary">{{ ucfirst($project->package_set) }}</span>
                                @endif
                            </div>
                            <h6 class="mb-1">{{ $project->name }}</h6>
                            <p class="text-muted small mb-3">
                                {{ $project->file_count }} file{{ $project->file_count === 1 ? '' : 's' }}
                                &bull; {{ number_format($project->size_bytes / 1024, 1) }} KB
                            </p>
                            <div class="mt-auto d-flex gap-2">
                                <a href="{{ route('student.ide.edit', $project) }}" class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="ti ti-edit me-1"></i>Open
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-project"
                                        data-id="{{ $project->id }}" data-name="{{ $project->name }}"
                                        data-url="{{ route('student.ide.destroy', $project) }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- New Project Modal --}}
<div class="modal fade" id="newProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('student.ide.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Project name</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required
                               placeholder="e.g. My First Website">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="kind" id="kindWeb" value="web" checked>
                                <label class="form-check-label" for="kindWeb">
                                    <i class="ti ti-brand-html5 ti-xs me-1"></i>HTML / CSS / JS
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="kind" id="kindPython" value="python">
                                <label class="form-check-label" for="kindPython">
                                    <i class="ti ti-brand-python ti-xs me-1"></i>Python
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-1" id="packageSetWrap" style="display:none;">
                        <label class="form-label">Packages</label>
                        <select name="package_set" class="form-select">
                            @foreach (config('ide.package_sets') as $key => $set)
                                <option value="{{ $key }}">{{ $set['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
