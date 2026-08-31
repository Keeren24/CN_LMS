<?php

namespace App\Services\Ide;

use App\Models\Ide\IdeFile;
use App\Models\Ide\IdeProject;
use App\Models\Ide\IdeSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    /**
     * Create a project from the config-seeded starter template for the given kind.
     */
    public function create(int $studentId, string $name, string $kind, ?string $packageSet = null): IdeProject
    {
        if (! in_array($kind, ['web', 'python'], true)) {
            throw ValidationException::withMessages(['kind' => 'Invalid project type.']);
        }

        $existing = IdeProject::where('student_id', $studentId)->count();
        if ($existing >= config('ide.max_projects_per_student')) {
            throw ValidationException::withMessages([
                'name' => 'You have reached the maximum number of projects ('.config('ide.max_projects_per_student').'). Delete one to create another.',
            ]);
        }

        if ($kind === 'python') {
            $packageSet = $packageSet && array_key_exists($packageSet, config('ide.package_sets'))
                ? $packageSet
                : 'basic';
        } else {
            $packageSet = null;
        }

        $template = config("ide.templates.$kind");
        $slug = $this->uniqueSlug($studentId, $name);

        return DB::connection('lms')->transaction(function () use ($studentId, $name, $slug, $kind, $packageSet, $template) {
            $project = IdeProject::create([
                'student_id' => $studentId,
                'name' => $name,
                'slug' => $slug,
                'kind' => $kind,
                'package_set' => $packageSet,
                'entry_file' => $template['entry_file'],
                'last_opened_at' => now(),
            ]);

            $totalSize = 0;
            foreach ($template['files'] as $path => $content) {
                $size = strlen($content);
                $totalSize += $size;
                IdeFile::create([
                    'project_id' => $project->id,
                    'path' => $path,
                    'content' => $content,
                    'is_binary' => false,
                    'size_bytes' => $size,
                ]);
            }

            $project->update([
                'file_count' => count($template['files']),
                'size_bytes' => $totalSize,
            ]);

            return $project;
        });
    }

    private function uniqueSlug(int $studentId, string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i = 1;
        // withTrashed(): the unique index still holds the slug of a soft-deleted
        // project, so a scoped-out (non-trashed) check alone would let a new
        // project collide with an old, deleted one of the same name.
        while (IdeProject::withTrashed()->where('student_id', $studentId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /**
     * Validate and persist a single file's content, keeping the parent
     * project's file_count/size_bytes rollups in sync.
     */
    public function saveFile(IdeProject $project, string $path, string $content): IdeFile
    {
        $this->assertValidPath($path);

        $maxFileSize = config('ide.max_file_size_bytes');
        $size = strlen($content);
        if ($size > $maxFileSize) {
            throw ValidationException::withMessages([
                'content' => 'File exceeds the '.number_format($maxFileSize / 1024).' KB limit.',
            ]);
        }

        return DB::connection('lms')->transaction(function () use ($project, $path, $content, $size) {
            $file = IdeFile::where('project_id', $project->id)->where('path', $path)->first();
            $previousSize = $file?->size_bytes ?? 0;
            $isNewFile = ! $file;

            if ($isNewFile) {
                $fileCount = IdeFile::where('project_id', $project->id)->count();
                if ($fileCount >= config('ide.max_files_per_project')) {
                    throw ValidationException::withMessages([
                        'path' => 'This project has reached the maximum of '.config('ide.max_files_per_project').' files.',
                    ]);
                }
            }

            $projectedTotal = $project->size_bytes - $previousSize + $size;
            if ($projectedTotal > config('ide.max_project_size_bytes')) {
                throw ValidationException::withMessages([
                    'content' => 'This change would exceed the project size limit ('.number_format(config('ide.max_project_size_bytes') / 1024 / 1024, 1).' MB total).',
                ]);
            }

            $file = IdeFile::updateOrCreate(
                ['project_id' => $project->id, 'path' => $path],
                ['content' => $content, 'is_binary' => false, 'size_bytes' => $size]
            );

            $project->increment('size_bytes', $size - $previousSize);
            if ($isNewFile) {
                $project->increment('file_count');
            }

            return $file;
        });
    }

    public function deleteFile(IdeProject $project, string $path): void
    {
        if ($path === $project->entry_file) {
            throw ValidationException::withMessages(['path' => 'The entry file cannot be deleted.']);
        }

        $file = IdeFile::where('project_id', $project->id)->where('path', $path)->first();
        if (! $file) {
            return;
        }

        $file->delete();
        $project->decrement('size_bytes', $file->size_bytes);
        $project->decrement('file_count');
    }

    public function renameFile(IdeProject $project, string $oldPath, string $newPath): IdeFile
    {
        $this->assertValidPath($newPath);

        if (IdeFile::where('project_id', $project->id)->where('path', $newPath)->exists()) {
            throw ValidationException::withMessages(['path' => 'A file already exists at that path.']);
        }

        $file = IdeFile::where('project_id', $project->id)->where('path', $oldPath)->firstOrFail();
        $file->update(['path' => $newPath]);

        if ($project->entry_file === $oldPath) {
            $project->update(['entry_file' => $newPath]);
        }

        return $file;
    }

    public function snapshot(IdeProject $project, string $label): IdeSnapshot
    {
        $files = IdeFile::where('project_id', $project->id)
            ->get(['path', 'content', 'is_binary'])
            ->toArray();

        $count = IdeSnapshot::where('project_id', $project->id)->count();
        if ($count >= config('ide.max_snapshots_per_project')) {
            IdeSnapshot::where('project_id', $project->id)
                ->oldest('created_at')
                ->limit($count - config('ide.max_snapshots_per_project') + 1)
                ->delete();
        }

        return IdeSnapshot::create([
            'project_id' => $project->id,
            'label' => $label,
            'files_json' => $files,
            'created_at' => now(),
        ]);
    }

    public function restore(IdeProject $project, IdeSnapshot $snapshot): void
    {
        DB::connection('lms')->transaction(function () use ($project, $snapshot) {
            IdeFile::where('project_id', $project->id)->delete();

            $totalSize = 0;
            foreach ($snapshot->files_json as $f) {
                $size = strlen($f['content'] ?? '');
                $totalSize += $size;
                IdeFile::create([
                    'project_id' => $project->id,
                    'path' => $f['path'],
                    'content' => $f['content'] ?? null,
                    'is_binary' => $f['is_binary'] ?? false,
                    'size_bytes' => $size,
                ]);
            }

            $project->update([
                'file_count' => count($snapshot->files_json),
                'size_bytes' => $totalSize,
            ]);
        });
    }

    private function assertValidPath(string $path): void
    {
        if ($path === '' || strlen($path) > 255) {
            throw ValidationException::withMessages(['path' => 'Invalid file path.']);
        }

        if (Str::contains($path, '..') || Str::startsWith($path, '/')) {
            throw ValidationException::withMessages(['path' => 'Invalid file path.']);
        }

        if (! preg_match('/^[A-Za-z0-9._\-\/]{1,255}$/', $path)) {
            throw ValidationException::withMessages(['path' => 'File paths may only contain letters, numbers, dots, dashes, underscores and slashes.']);
        }
    }
}
