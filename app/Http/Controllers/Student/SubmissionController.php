<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\HomeworkSubmission;
use Illuminate\Support\Facades\Auth;

class SubmissionController extends Controller
{
    public function show($id)
    {
        $submission = HomeworkSubmission::with([
                'homework',
                'answers.question.options',
            ])
            ->where('id', $id)
            ->where('student_id', Auth::user()->student_id)
            ->firstOrFail();

        $finalised = $submission->marking_status === HomeworkSubmission::STATUS_FINALISED;

        return view('student.homework.submission', compact('submission', 'finalised'));
    }
}
