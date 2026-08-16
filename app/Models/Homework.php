<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Homework extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'homeworks';
    protected $primaryKey = 'id';

    protected $fillable = [
        'course_id',
        'tutor_id',
        'title',
        'auto_marking',
        'status',
        'pass_threshold',
        'threshold_type',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(HomeworkQuestion::class);
    }

    public function submissions()
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    public function classAssignments()
    {
        return $this->hasMany(HomeworkStudentClass::class);
    }

    public function studentClasses()
    {
        return $this->belongsToMany(
            StudentClass::class,
            'homework_student_class',
            'homework_id',
            'student_class_id'
        );
    }
}
