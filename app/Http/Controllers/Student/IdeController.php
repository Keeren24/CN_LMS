<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Ide\IdeProject;
use App\Services\Ide\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdeController extends Controller
{
    public function __construct(private ProjectService $projects)
    {
    }

    public function index()
    {
        $studentId = Auth::user()->student_id;

        $projects = IdeProject::where('student_id', $studentId)
            ->orderByDesc('last_opened_at')
            ->get();

        return view('student.ide.index', [
            'projects' => $projects,
            'maxProjects' => config('ide.max_projects_per_student'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', 'in:web,python'],
            'package_set' => ['nullable', 'string'],
        ]);

        $project = $this->projects->create(
            Auth::user()->student_id,
            $data['name'],
            $data['kind'],
            $data['package_set'] ?? null
        );

        return redirect()->route('student.ide.edit', $project)->with('success', 'Project created.');
    }

    public function edit(IdeProject $project)
    {
        $this->authorizeProject($project);

        $project->update(['last_opened_at' => now()]);

        return view('student.ide.editor', [
            'project' => $project,
        ]);
    }

    public function files(IdeProject $project)
    {
        $this->authorizeProject($project);

        return response()->json([
            'project' => $project->only(['id', 'name', 'slug', 'kind', 'package_set', 'entry_file', 'size_bytes', 'file_count']),
            'files' => $project->files()->orderBy('path')->get(['id', 'path', 'is_binary', 'size_bytes', 'updated_at']),
        ]);
    }

    public function fileContent(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $path = $request->query('path', '');
        $file = $project->files()->where('path', $path)->firstOrFail();

        return response()->json([
            'path' => $file->path,
            'content' => $file->content,
            'updated_at' => $file->updated_at,
        ]);
    }

    public function saveFile(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'path' => ['required', 'string'],
            'content' => ['present', 'string'],
        ]);

        $file = $this->projects->saveFile($project, $data['path'], $data['content']);

        return response()->json([
            'saved' => true,
            'updated_at' => $file->updated_at,
            'size_bytes' => $file->size_bytes,
        ]);
    }

    public function createFile(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $file = $this->projects->saveFile($project, $data['path'], '');

        return response()->json(['path' => $file->path], 201);
    }

    public function renameFile(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'old_path' => ['required', 'string'],
            'new_path' => ['required', 'string'],
        ]);

        $file = $this->projects->renameFile($project, $data['old_path'], $data['new_path']);

        return response()->json(['path' => $file->path]);
    }

    public function deleteFile(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $this->projects->deleteFile($project, $data['path']);

        return response()->json(['deleted' => true]);
    }

    public function snapshot(IdeProject $project, Request $request)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
        ]);

        $snapshot = $this->projects->snapshot($project, $data['label']);

        return response()->json(['id' => $snapshot->id, 'label' => $snapshot->label, 'created_at' => $snapshot->created_at]);
    }

    public function snapshots(IdeProject $project)
    {
        $this->authorizeProject($project);

        return response()->json($project->snapshots()->get(['id', 'label', 'created_at']));
    }

    public function restore(IdeProject $project, int $snapshot)
    {
        $this->authorizeProject($project);

        $snap = $project->snapshots()->findOrFail($snapshot);
        $this->projects->restore($project, $snap);

        return response()->json(['restored' => true]);
    }

    public function destroy(IdeProject $project)
    {
        $this->authorizeProject($project);

        $project->files()->delete();
        $project->snapshots()->delete();
        $project->delete();

        return redirect()->route('student.ide.index')->with('success', 'Project deleted.');
    }

    private function authorizeProject(IdeProject $project): void
    {
        abort_unless((int) $project->student_id === (int) Auth::user()->student_id, 404);
    }
}
