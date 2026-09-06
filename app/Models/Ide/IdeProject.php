<?php

namespace App\Models\Ide;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdeProject extends Model
{
    use SoftDeletes;

    protected $connection = 'lms';
    protected $table = 'ide_projects';

    protected $fillable = [
        'student_id',
        'name',
        'slug',
        'kind',
        'package_set',
        'entry_file',
        'file_count',
        'size_bytes',
        'last_opened_at',
    ];

    protected function casts(): array
    {
        return [
            'last_opened_at' => 'datetime',
        ];
    }

    public function files()
    {
        return $this->hasMany(IdeFile::class, 'project_id');
    }

    public function snapshots()
    {
        return $this->hasMany(IdeSnapshot::class, 'project_id')->latest('created_at');
    }
}
