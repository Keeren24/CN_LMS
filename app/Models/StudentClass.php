<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentClass extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkgroup;

    protected $table = 'student_class';
    protected $primaryKey = 'id';

    protected $fillable = [
        'branch_id',
        'tutor_id',
        'name',
        'class_day',
        'start_time',
        'end_time',
        'status',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'cn_class_id')->where('status', 'active');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'student_class_id');
    }

    public function homeworks()
    {
        return $this->belongsToMany(
            Homework::class,
            'homework_student_class',
            'student_class_id',
            'homework_id'
        );
    }
}
