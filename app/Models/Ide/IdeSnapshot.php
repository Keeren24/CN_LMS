<?php

namespace App\Models\Ide;

use Illuminate\Database\Eloquent\Model;

class IdeSnapshot extends Model
{
    public $timestamps = false;

    protected $connection = 'lms';
    protected $table = 'ide_snapshots';

    protected $fillable = [
        'project_id',
        'label',
        'files_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'files_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(IdeProject::class, 'project_id');
    }
}
