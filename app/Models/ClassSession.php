<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory, BelongsToWorkgroup;

    protected $table = 'class_sessions';

    protected $fillable = [
        'student_class_id',
        'workgroup_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'is_manual_override',
        'lesson_id',
    ];

    protected $casts = [
        'date'               => 'date',
        'is_manual_override' => 'boolean',
    ];

    public const STATUS_SCHEDULED    = 'scheduled';
    public const STATUS_HOLIDAY      = 'holiday';
    public const STATUS_CENTER_BREAK = 'center_break';
    public const STATUS_CANCELLED    = 'cancelled';
    public const STATUS_COMPLETED    = 'completed';

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'student_class_id');
    }

    public function isTeaching(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_COMPLETED], true);
    }
}
