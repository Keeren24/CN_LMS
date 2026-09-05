@extends('layouts/layoutMaster')

@section('title', $project->name)

{{-- Paints the correct background immediately, before the main Vite-served
     stylesheet (core.scss) finishes loading — this page pulls in a much
     larger module graph (Monaco, xterm, SweetAlert2) than most pages, so
     that stylesheet can take noticeably longer to arrive in dev, causing a
     visible flash to the wrong color without this.

     Two layers, in order:
     1. A plain <style> tag using the admin-mode/admin-colorPref cookies
        (mirrors Helpers::appClasses()'s own resolution) — correct without
        JS, and correct on most repeat visits.
     2. A synchronous inline <script> (runs before first paint, since it's
        neither async/defer/module) that corrects layer 1 against
        localStorage — the template customizer's *actual* source of truth
        (see template-customizer.js's _getSetting/_setSetting). The cookies
        only get (re)written by the customizer's client-side JS *after* a
        page has already rendered, so they lag one navigation behind
        localStorage — without this second layer, a student who toggled
        the theme client-side would see the theme flash back to their
        previous choice on every single page load, not just occasionally. --}}
@section('critical-style')
    @php
        $__criticalMode = $_COOKIE['admin-mode'] ?? 'light';
        if ($__criticalMode === 'system') {
            $__criticalMode = $_COOKIE['admin-colorPref'] ?? 'light';
        }
        $__criticalBg = $__criticalMode === 'dark' ? '#25293c' : '#f8f7fa';
    @endphp
    <style>html, body { background-color: {{ $__criticalBg }}; }</style>
    <script>
        (function () {
            try {
                var html = document.documentElement;
                var layoutName = html.getAttribute('data-template') || 'vertical-menu-template';
                var theme = localStorage.getItem('templateCustomizer-' + layoutName + '--Theme') || html.getAttribute('data-bs-theme');
                if (!theme || theme === 'system') {
                    theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                html.style.backgroundColor = theme === 'dark' ? '#25293c' : '#f8f7fa';
            } catch (e) {}
        })();
    </script>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
    <style>
        .ide-shell { position: relative; height: calc(100vh - 12rem); min-height: 480px; display: flex; flex-direction: column; border: 1px solid var(--bs-border-color); border-radius: 0.5rem; overflow: hidden; background: var(--bs-paper-bg, #2f3349); }
        /* Full takeover while Monaco + the file tree boot, similar to a cloud
           shell's "provisioning your environment" screen — hides the empty
           editor/sidebar mid-boot rather than letting them flash in piecemeal. */
        .ide-loading { position: absolute; inset: 0; z-index: 20; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; background: var(--bs-paper-bg, #2f3349); transition: opacity .25s ease; }
        .ide-loading.ide-loading-hidden { opacity: 0; pointer-events: none; }
        .ide-loading-icon { width: 3.5rem; height: 3.5rem; border-radius: 0.75rem; background: var(--bs-primary-bg-subtle, rgba(105,108,255,0.12)); display: flex; align-items: center; justify-content: center; }
        .ide-loading-icon i { font-size: 1.75rem; color: var(--bs-primary); }
        .ide-loading-text { font-size: 0.9rem; color: var(--bs-secondary-color); }
        .ide-loading-error { color: var(--bs-danger); text-align: center; max-width: 320px; }
        .ide-toolbar { border-bottom: 1px solid var(--bs-border-color); background: var(--bs-paper-bg, #2f3349); }
        .ide-body { flex: 1 1 auto; display: flex; min-height: 0; }
        .ide-sidebar { width: 220px; flex-shrink: 0; border-inline-end: 1px solid var(--bs-border-color); overflow-y: auto; background: var(--bs-body-bg); }
        .ide-main { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; }
        .ide-tabs { display: flex; overflow-x: auto; border-bottom: 1px solid var(--bs-border-color); background: var(--bs-body-bg); flex-shrink: 0; }
        .ide-tab { cursor: pointer; font-size: 0.8rem; white-space: nowrap; }
        .ide-tab.active { background: var(--bs-paper-bg, #2f3349); border-bottom: 2px solid var(--bs-primary); }
        .ide-tab i.ti-x { cursor: pointer; }
        #ideEditorContainer { flex: 1 1 auto; min-height: 0; }
        .ide-file-row { font-size: 0.82rem; }
        .ide-file-row:hover, .ide-file-row.active { background: var(--bs-primary-bg-subtle, rgba(105,108,255,0.08)); }
        /* Actions stay in-flow (not display:none) so hovering never reflows
           the row — only their opacity changes, avoiding any layout shift. */
        .ide-file-actions { display: inline-flex; opacity: 0; pointer-events: none; }
        .ide-file-row:hover .ide-file-actions, .ide-file-row.active .ide-file-actions { opacity: 1; pointer-events: auto; }
        .ide-output { width: 42%; min-width: 320px; border-inline-start: 1px solid var(--bs-border-color); display: flex; flex-direction: column; background: #1e1e2f; }
        .ide-output-header { background: var(--bs-paper-bg, #2f3349); border-bottom: 1px solid var(--bs-border-color); }
        #ideWebFrame { flex: 1 1 60%; border: none; background: #fff; width: 100%; }
        #ideWebConsole { flex: 0 0 auto; max-height: 30%; overflow-y: auto; background: #12121c; color: #ddd; font-family: ui-monospace, monospace; font-size: 0.78rem; padding: 0.5rem; }
        .ide-console-error { color: #ff6b6b; }
        .ide-console-warn { color: #ffd166; }
        #idePyTerminal { flex: 1 1 auto; min-height: 0; padding: 0.25rem; }
        /* Both max-heights are in vh (not %) and deliberately share the same
           unit system with a safety margin between them, so a single figure
           always scales down to fit fully inside the panel with no scrolling —
           a % cap on the panel vs. a vh cap on the image would drift apart
           depending on viewport size and clip the image regardless. */
        #idePyFigures { flex: 0 0 auto; max-height: 42vh; overflow-y: auto; background: #16161f; border-top: 1px solid rgba(255,255,255,0.08); padding: 0.5rem; display: none; }
        #idePyFigures:has(.ide-figure) { display: block; }
        .ide-figure { display: block; width: auto; max-width: 100%; max-height: 38vh; object-fit: contain; margin-inline: auto; }
        /* The 3-panel layout (file tree + editor + output) and the toolbar
           (6 unwrapped buttons) were never designed for narrow viewports —
           below this, the toolbar clips and the output panel becomes
           unreachable rather than gracefully reflowing. Block outright
           instead of shipping a half-broken editor; matches the breakpoint
           the rest of this template already treats as "not desktop"
           (Bootstrap's lg). */
        .ide-mobile-block { display: none; }
        @media (max-width: 991.98px) {
            .ide-shell { display: none; }
            .ide-mobile-block { display: flex; }
        }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/student-ide-editor.js'])
@endsection

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('student.ide.index') }}" class="btn btn-icon btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left"></i>
            </a>
            <h5 class="mb-0">{{ $project->name }}</h5>
            <span class="badge {{ $project->kind === 'python' ? 'bg-label-info' : 'bg-label-warning' }}">
                {{ $project->kind === 'python' ? 'Python' : 'Web' }}
            </span>
        </div>
        <span id="ideSaveStatus" class="small text-muted">All changes saved</span>
    </div>

    <div class="ide-mobile-block flex-column align-items-center text-center py-5">
        <i class="ti ti-device-desktop ti-lg text-muted mb-3" style="font-size: 3rem;"></i>
        <h5 class="mb-2">Code Editor needs a bigger screen</h5>
        <p class="text-muted mb-3" style="max-width: 360px;">
            The editor, file tree, and output panel don't fit well on a phone-sized screen.
            Please switch to a tablet or a desktop/laptop to continue working on this project.
        </p>
        <a href="{{ route('student.ide.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Code Editor
        </a>
    </div>

    @php
        $ideConfig = [
            'projectUrl' => route('student.ide.edit', $project),
            'projectId' => $project->id,
            'projectSlug' => $project->slug,
            'kind' => $project->kind,
            'entryFile' => $project->entry_file,
            'pyodideCdn' => config('ide.pyodide_cdn'),
            'packages' => $project->package_set
                ? (config("ide.package_sets.{$project->package_set}.packages") ?? [])
                : [],
        ];
    @endphp

    <div id="ideRoot" class="ide-shell" data-config='@json($ideConfig)'>

        <div id="ideLoadingOverlay" class="ide-loading">
            <div class="ide-loading-icon"><i class="ti ti-code"></i></div>
            <div class="spinner-border text-primary" role="status" id="ideLoadingSpinner"></div>
            <div class="ide-loading-text" id="ideLoadingText">Setting up your code editor…</div>
        </div>

        <div class="ide-toolbar d-flex align-items-center gap-2 px-2 py-2">
            <button type="button" id="ideRunBtn" class="btn btn-success btn-sm">
                <i class="ti ti-player-play-filled ti-xs me-1"></i>Run
            </button>
            <button type="button" id="ideStopBtn" class="btn btn-danger btn-sm" disabled>
                <i class="ti ti-player-stop-filled ti-xs me-1"></i>Stop
            </button>
            <div class="vr mx-1"></div>
            <button type="button" id="ideNewFileBtn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#ideNewFileModal">
                <i class="ti ti-file-plus ti-xs me-1"></i>New File
            </button>
            <button type="button" id="ideSnapshotBtn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#ideSnapshotModal">
                <i class="ti ti-device-floppy ti-xs me-1"></i>Snapshot
            </button>
            <button type="button" id="ideHistoryBtn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#ideHistoryModal">
                <i class="ti ti-history ti-xs me-1"></i>History
            </button>
            <button type="button" id="ideDownloadBtn" class="btn btn-outline-secondary btn-sm ms-auto">
                <i class="ti ti-download ti-xs me-1"></i>Download
            </button>
        </div>

        <div class="ide-body">
            <div class="ide-sidebar" id="ideFileTree"></div>

            <div class="ide-main">
                <div class="ide-tabs" id="ideTabs"></div>
                <div id="ideEditorContainer"></div>
            </div>

            <div class="ide-output">
                @if ($project->kind === 'web')
                    <div class="ide-output-header px-2 py-1 small text-muted">Preview</div>
                    <iframe id="ideWebFrame" sandbox="allow-scripts allow-modals allow-forms"></iframe>
                    <div id="ideWebConsole"></div>
                @else
                    <div class="ide-output-header px-2 py-1 small text-muted">Console</div>
                    <div id="idePyTerminal"></div>
                    <div id="idePyFigures"></div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- New File --}}
<div class="modal fade" id="ideNewFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ideNewFileForm">
                <div class="modal-header">
                    <h5 class="modal-title">New File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="ideNewFilePath" class="form-label">File path</label>
                    <input type="text" id="ideNewFilePath" class="form-control" placeholder="e.g. about.html or js/utils.js" autocomplete="off" required>
                    <div class="form-text">Use a slash to place it in a folder, e.g. <code>css/style.css</code>.</div>
                    <div class="text-danger small mt-2 d-none" id="ideNewFileError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="ideNewFileSpinner"></span>Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Snapshot --}}
<div class="modal fade" id="ideSnapshotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="ideSnapshotForm">
                <div class="modal-header">
                    <h5 class="modal-title">Save Restore Point</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Saves the current state of every file in this project as a restore point you can come back to later.</p>
                    <label for="ideSnapshotLabel" class="form-label">Label</label>
                    <input type="text" id="ideSnapshotLabel" class="form-control" autocomplete="off" required>
                    <div class="text-danger small mt-2 d-none" id="ideSnapshotError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="ideSnapshotSpinner"></span>Save Snapshot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Restore points --}}
<div class="modal fade" id="ideHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restore Points</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Snapshots save the current state of every file. Restoring one replaces your current files — this can't be undone.</p>
                <div id="ideSnapshotList" class="list-group">
                    <div class="text-muted small px-2 py-3 text-center">Loading…</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
