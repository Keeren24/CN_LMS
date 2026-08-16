<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkQuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'option_label',
        'option_text',
        'is_correct',
    ];

    public function question()
    {
        return $this->belongsTo(HomeworkQuestion::class);
    }
}
