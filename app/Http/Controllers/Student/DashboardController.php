<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassPerformance;
use App\Models\Homework;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user       = Auth::user();
        $student_id = $user->student_id;
        $class_id   = Student::where('id', $student_id)->value('cn_class_id');
        $classInfo  = $class_id ? StudentClass::find($class_id) : null;
        $className  = $classInfo->name ?? '';

        // ── Greeting ───────────────────────────────────────────────────────────
        $hour      = now()->hour;
        $greeting  = match(true) {
            $hour < 12  => 'Good morning',
            $hour < 17  => 'Good afternoon',
            default     => 'Good evening',
        };
        $firstName = explode(' ', $user->name)[0] ?? 'there';

        // ── Homeworks (grouped by course, with submissions & marks) ────────────
        $homeworks   = collect();
        $subjectTotals = collect();

        if ($class_id) {
            $homeworks = Homework::whereHas('studentClasses', function ($q) use ($class_id) {
                    $q->where('student_class_id', $class_id)
                      ->where(function ($r) {
                          $r->whereNull('release_at')->orWhere('release_at', '<=', now());
                      });
                })
                ->with([
                    'course',
                    'questions',
                    'submissions' => function ($q) use ($student_id) {
                        $q->where('student_id', $student_id)->orderBy('attempt_number', 'desc');
                    },
                ])
                ->withSum('questions', 'marks')
                ->orderBy('created_at', 'desc')
                ->get();

            // Per-subject accumulated points
            $subjectTotals = $homeworks
                ->groupBy('course_id')
                ->map(function ($hwGroup) {
                    $courseName = $hwGroup->first()->course->name ?? 'Unknown';
                    $achieved   = 0;
                    $possible   = 0;

                    foreach ($hwGroup as $hw) {
                        $possible += $hw->questions_sum_marks ?? $hw->questions->sum('marks');
                        $best      = $hw->submissions->max('final_total_marks');
                        $achieved += $best ?? 0;
                    }

                    return [
                        'course'   => $courseName,
                        'achieved' => $achieved,
                        'possible' => $possible,
                        'pct'      => $possible > 0 ? round(($achieved / $possible) * 100, 1) : 0,
                    ];
                })
                ->values();
        }

        // Derived homework counts for welcome banner
        $totalHw   = $homeworks->count();
        $submitted = $homeworks->filter(fn($hw) => $hw->submissions->isNotEmpty())->count();
        $passed    = $homeworks->filter(function ($hw) {
            $possible  = $hw->questions_sum_marks ?? $hw->questions->sum('marks');
            $best      = $hw->submissions->max('final_total_marks');
            $threshold = $hw->pass_threshold ?? 70;
            return $possible > 0 && $best !== null && ($best / $possible * 100) >= $threshold;
        })->count();
        $pending   = $homeworks->filter(fn($hw) =>
            $hw->submissions->isNotEmpty() &&
            $hw->submissions->first()->marking_status !== 'finalised'
        )->count();

        // ── Class Points history (for chart) ───────────────────────────────────
        $classPoints = ClassPerformance::where('student_id', $student_id)
            ->when($class_id, fn($q) => $q->where('class_id', $class_id))
            ->orderBy('date', 'asc')
            ->get(['date', 'points', 'remarks']);

        $totalPoints = $classPoints->sum('points');

        return view('student.dashboard', compact(
            'firstName', 'greeting', 'className', 'classInfo',
            'homeworks', 'subjectTotals',
            'totalHw', 'submitted', 'passed', 'pending',
            'classPoints', 'totalPoints'
        ));
    }
}
