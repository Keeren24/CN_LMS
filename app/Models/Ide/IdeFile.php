<?php

namespace App\Models\Ide;

use Illuminate\Database\Eloquent\Model;

class IdeFile extends Model
{
    protected $connection = 'lms';
    protected $table = 'ide_files';

    protected $fillable = [
        'project_id',
        'path',
        'content',
        'blob_path',
        'is_binary',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'is_binary' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(IdeProject::class, 'project_id');
    }
}
