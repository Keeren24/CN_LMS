<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkQuestion extends Model
{
    protected $fillable = [
        'homework_id',
        'question_no',
        'question_text',
        'image_path',
        'question_type',
        'marks',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function options()
    {
        return $this->hasMany(HomeworkQuestionOption::class, 'question_id');
    }

    public function modelAnswer()
    {
        return $this->hasOne(HomeworkQuestionAnswer::class, 'question_id');
    }
}
