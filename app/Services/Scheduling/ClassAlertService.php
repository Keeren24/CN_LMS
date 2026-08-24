<?php

namespace App\Services\Scheduling;

use App\Models\CalendarEvent;
use App\Models\ClassSession;
use App\Models\Student;
use App\Models\StudentClass;
use Carbon\Carbon;

/**
 * Builds the live in-app alerts shown to a student: cancelled upcoming classes,
 * their next class, and upcoming centre events. Computed on page load, so no
 * notifications table or queue is needed for the in-app surface.
 */
class ClassAlertService
{
    /**
     * @return array{className:?string, cancelled:array, nextClass:?array, events:array}
     */
    public function forStudent($user): array
    {
        $empty = ['className' => null, 'cancelled' => [], 'nextClass' => null, 'events' => []];

        $studentId = $user->student_id ?? null;
        if (! $studentId) {
            return $empty;
        }

        $classId = Student::where('id', $studentId)->value('cn_class_id');
        if (! $classId) {
            return $empty;
        }

        $class = StudentClass::find($classId);
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(30);

        $cancelled = ClassSession::where('student_class_id', $classId)
            ->where('status', ClassSession::STATUS_CANCELLED)
            ->whereBetween('date', [$today, $horizon])
            ->orderBy('date')
            ->get()
            ->map(fn (ClassSession $s) => [
                'date' => $s->date->format('l, d M Y'),
                'reason' => $s->reason,
            ])->all();

        $next = ClassSession::where('student_class_id', $classId)
            ->where('status', ClassSession::STATUS_SCHEDULED)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->first();

        $nextClass = $next ? [
            'date' => $next->date->format('l, d M Y'),
            'time' => $next->start_time ? substr($next->start_time, 0, 5).'–'.substr($next->end_time, 0, 5) : null,
            'isThisWeek' => $next->date->lte($today->copy()->endOfWeek()),
        ] : null;

        $events = CalendarEvent::whereDate('start_date', '>=', $today)
            ->whereDate('start_date', '<=', $horizon)
            ->where(function ($q) use ($classId, $studentId) {
                $q->whereDoesntHave('targets')
                    ->orWhereHas('targets', fn ($t) => $t->where('target_type', 'class')->where('target_id', $classId))
                    ->orWhereHas('targets', fn ($t) => $t->where('target_type', 'student')->where('target_id', $studentId));
            })
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn (CalendarEvent $e) => [
                'title' => $e->title,
                'date' => $e->start_date->format('d M Y'),
                'color' => $e->color,
            ])->all();

        return [
            'className' => $class?->name,
            'cancelled' => $cancelled,
            'nextClass' => $nextClass,
            'events' => $events,
        ];
    }
}
