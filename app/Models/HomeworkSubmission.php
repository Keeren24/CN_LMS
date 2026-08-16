<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkSubmission extends Model
{
    protected $fillable = [
        'homework_id',
        'student_id',
        'attempt_number',
        'submitted_at',
        'auto_total_marks',
        'tutor_total_marks',
        'final_total_marks',
        'marking_status',
    ];

    const STATUS_DRAFT         = 'draft';
    const STATUS_SUBMITTED     = 'submitted';
    const STATUS_AUTO_CHECKED  = 'auto_checked';
    const STATUS_TUTOR_CHECKED = 'tutor_checked';
    const STATUS_FINALISED     = 'finalised';

    protected $casts = ['submitted_at' => 'datetime'];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function answers()
    {
        return $this->hasMany(HomeworkAnswer::class, 'submission_id');
    }
}
