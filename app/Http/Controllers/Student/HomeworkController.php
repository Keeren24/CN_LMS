<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkAnswer;
use App\Models\HomeworkQuestionOption;
use App\Models\HomeworkSubmission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeworkController extends Controller
{
    private function studentClassId(): int
    {
        return Student::where('id', Auth::user()->student_id)->value('cn_class_id');
    }

    public function index()
    {
        $student_id = Auth::user()->student_id;
        $class_id   = $this->studentClassId();

        $homeworks = Homework::whereHas('studentClasses', function ($q) use ($class_id) {
                $q->where('student_class_id', $class_id)
                  ->where(function ($r) {
                      $r->whereNull('release_at')->orWhere('release_at', '<=', now());
                  });
            })
            ->with([
                'course',
                'questions',
                'submissions' => fn($q) => $q->where('student_id', $student_id)->orderByDesc('attempt_number'),
            ])
            ->withSum('questions', 'marks')
            ->latest()
            ->get();

        return view('student.homework.index', compact('homeworks'));
    }

    public function view($id)
    {
        $student_id = Auth::user()->student_id;
        $class_id   = $this->studentClassId();

        $homework = Homework::whereHas('studentClasses', function ($q) use ($class_id) {
                $q->where('student_class_id', $class_id)
                  ->where(function ($r) {
                      $r->whereNull('release_at')->orWhere('release_at', '<=', now());
                  });
            })
            ->with(['questions.options'])
            ->findOrFail($id);

        $latestSubmission = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_id', $student_id)
            ->orderByDesc('attempt_number')
            ->first();

        if ($latestSubmission) {
            $possible  = $homework->questions->sum('marks');
            $threshold = $homework->pass_threshold ?? 70;
            $bestMarks = HomeworkSubmission::where('homework_id', $homework->id)
                ->where('student_id', $student_id)
                ->max('final_total_marks');

            $passed = $possible > 0 && $bestMarks !== null && ($bestMarks / $possible * 100) >= $threshold;

            if ($passed) {
                return redirect()->route('student.homework.index')
                    ->with('info', 'You have already passed this homework.');
            }

            if ($latestSubmission->marking_status !== 'finalised') {
                return redirect()->route('student.homework.submission.view', $latestSubmission->id)
                    ->with('info', 'Your submission is pending marking.');
            }
        }

        return view('student.homework.view', compact('homework', 'latestSubmission'));
    }

    public function submit(Request $request, $id)
    {
        $student_id = Auth::user()->student_id;
        $class_id   = $this->studentClassId();

        $homework = Homework::whereHas('studentClasses', function ($q) use ($class_id) {
                $q->where('student_class_id', $class_id)
                  ->where(function ($r) {
                      $r->whereNull('release_at')->orWhere('release_at', '<=', now());
                  });
            })
            ->with('questions.options')
            ->findOrFail($id);

        $lastAttempt = HomeworkSubmission::where('homework_id', $homework->id)
            ->where('student_id', $student_id)
            ->max('attempt_number') ?? 0;

        if ($lastAttempt > 0) {
            $lastSub = HomeworkSubmission::where('homework_id', $homework->id)
                ->where('student_id', $student_id)
                ->where('attempt_number', $lastAttempt)
                ->first();

            if ($lastSub && $lastSub->marking_status !== 'finalised') {
                return back()->with('error', 'Your previous attempt is still being marked.');
            }

            $possible  = $homework->questions->sum('marks');
            $threshold = $homework->pass_threshold ?? 70;
            $bestMarks = HomeworkSubmission::where('homework_id', $homework->id)
                ->where('student_id', $student_id)
                ->max('final_total_marks');

            if ($possible > 0 && $bestMarks !== null && ($bestMarks / $possible * 100) >= $threshold) {
                return back()->with('error', 'You have already passed this homework.');
            }
        }

        $submission = HomeworkSubmission::create([
            'homework_id'    => $homework->id,
            'student_id'     => $student_id,
            'attempt_number' => $lastAttempt + 1,
            'submitted_at'   => now(),
            'marking_status' => HomeworkSubmission::STATUS_SUBMITTED,
        ]);

        $autoTotal = 0;

        foreach ($homework->questions as $q) {
            $answerInput   = $request->answers[$q->id] ?? null;
            $autoMarks     = 0;
            $selectedOptId = null;

            if ($q->question_type === 'mcq' && $answerInput) {
                $option = HomeworkQuestionOption::find($answerInput);
                if ($option) {
                    $selectedOptId = $option->id;
                    if ($option->is_correct) {
                        $autoMarks  = $q->marks;
                        $autoTotal += $autoMarks;
                    }
                }
            }

            HomeworkAnswer::create([
                'submission_id'     => $submission->id,
                'question_id'       => $q->id,
                'selected_option_id'=> $selectedOptId,
                'answer_text'       => $q->question_type === 'subjective' ? $answerInput : null,
                'auto_marks'        => $autoMarks,
                'final_marks'       => $autoMarks,
            ]);
        }

        $submission->update([
            'auto_total_marks'  => $autoTotal,
            'final_total_marks' => $autoTotal,
        ]);

        return redirect()
            ->route('student.homework.submission.view', $submission->id)
            ->with('submitted', true);
    }
}
