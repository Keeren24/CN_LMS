<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeworkStudentClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'homework_student_class';
    protected $primaryKey = 'id';

    protected $fillable = [
        'homework_id',
        'student_class_id',
        'release_at',
    ];

    protected $casts = [
        'release_at' => 'datetime',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }
}
