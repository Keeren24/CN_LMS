<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassPerformance extends Model
{
    use HasFactory;

    protected $table = 'class_performances';
    protected $primaryKey = 'id';

    protected $fillable = [
        'student_id',
        'class_id',
        'tutor_id',
        'date',
        'points',
        'remarks',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentClass()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }
}
