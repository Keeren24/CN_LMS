<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkQuestionAnswer extends Model
{
    protected $fillable = [
        'question_id',
        'answer_text',
        'keywords',
    ];

    public function question()
    {
        return $this->belongsTo(HomeworkQuestion::class);
    }
}
