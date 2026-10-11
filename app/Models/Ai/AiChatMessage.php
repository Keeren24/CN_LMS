<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $connection = 'lms';
    protected $table = 'ai_chat_messages';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'course_id',
        'role',
        'content',
        'input_tokens',
        'output_tokens',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function scopeForThread(Builder $query, int $studentId, int $courseId): Builder
    {
        return $query->where('student_id', $studentId)->where('course_id', $courseId);
    }
}
