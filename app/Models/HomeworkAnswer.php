<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkAnswer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'selected_option_id',
        'answer_text',
        'auto_marks',
        'tutor_marks',
        'final_marks',
    ];

    public function submission()
    {
        return $this->belongsTo(HomeworkSubmission::class);
    }

    public function question()
    {
        return $this->belongsTo(HomeworkQuestion::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(HomeworkQuestionOption::class, 'selected_option_id');
    }
}
