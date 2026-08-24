<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\PublicHoliday;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function index()
    {
        $class = $this->studentClass();

        return view('student.calendar', [
            'className' => $class?->name,
            'hasClass' => $class !== null,
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'color']),
            'branchColor' => $class?->branch_info?->colorTag() ?? '#16a34a',
        ]);
    }

    /** Read-only feed: the student's class sessions + their holidays + events. */
    public function events(Request $request): JsonResponse
    {
        $startDate = substr((string) $request->query('start'), 0, 10);
        $endDate = substr((string) $request->query('end'), 0, 10);

        $class = $this->studentClass();
        $state = $class ? ($class->branch_info->state ?? null) : null;
        $state = $state ?: config('services.holidays.default_state');

        $events = PublicHoliday::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->applicableTo($state)
            ->get()
            ->map(fn (PublicHoliday $h) => $this->holidayEvent($h));

        if ($class) {
            $sessions = ClassSession::where('student_class_id', $class->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('status', [
                    ClassSession::STATUS_SCHEDULED,
                    ClassSession::STATUS_COMPLETED,
                    ClassSession::STATUS_CANCELLED,
                    ClassSession::STATUS_CENTER_BREAK,
                ])
                ->get();

            $attendance = ClassAttendance::where('student_id', Auth::user()->student_id)
                ->where('class_id', $class->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->mapWithKeys(fn ($a) => [$a->date->format('Y-m-d') => $a->status])
                ->all();

            $events = $events->concat($sessions->map(fn (ClassSession $s) => $this->sessionEvent($s, $class, $attendance)));
        }

        $studentId = Auth::user()->student_id ?? null;
        $classId = $class?->id;

        $calendarEvents = CalendarEvent::whereDate('start_date', '<=', $endDate ?: $startDate)
            ->where(function ($q) use ($startDate) {
                $q->whereDate('end_date', '>=', $startDate)->orWhereDate('start_date', '>=', $startDate);
            })
            ->where(function ($q) use ($classId, $studentId) {
                $q->whereDoesntHave('targets')
                    ->orWhereHas('targets', fn ($t) => $t->where('target_type', 'class')->where('target_id', $classId))
                    ->orWhereHas('targets', fn ($t) => $t->where('target_type', 'student')->where('target_id', $studentId));
            })
            ->get()
            ->map(fn (CalendarEvent $e) => $this->calendarEvent($e));

        return response()->json($events->concat($calendarEvents)->values()->all());
    }

    private function studentClass(): ?StudentClass
    {
        $studentId = Auth::user()->student_id ?? null;
        if (! $studentId) {
            return null;
        }

        $classId = Student::where('id', $studentId)->value('cn_class_id');

        return $classId ? StudentClass::with('branch_info')->find($classId) : null;
    }

    private function holidayEvent(PublicHoliday $h): array
    {
        $isNational = empty($h->states);

        return [
            'title' => $h->name,
            'start' => $h->date->format('Y-m-d'),
            'allDay' => true,
            'sortPriority' => 1,
            'classNames' => [$isNational ? 'ev-national' : 'ev-state'],
            'backgroundColor' => $isNational ? '#4f46e5' : '#0e7490',
            'borderColor' => $isNational ? '#4f46e5' : '#0e7490',
            'textColor' => '#ffffff',
            'extendedProps' => ['kind' => 'holiday'],
        ];
    }

    private function sessionEvent(ClassSession $s, StudentClass $class, array $attendance = []): array
    {
        $branchColor = $class->branch_info?->colorTag() ?? '#16a34a';
        $bg = match ($s->status) {
            ClassSession::STATUS_CANCELLED    => '#dc2626',
            ClassSession::STATUS_CENTER_BREAK => '#94a3b8',
            default                           => $branchColor,
        };

        $classNames = ['ev-session', 'ev-session-'.$s->status];
        if (in_array($s->status, [ClassSession::STATUS_SCHEDULED, ClassSession::STATUS_COMPLETED], true)) {
            $classNames[] = 'ev-branch-'.$class->branch_id;
        }

        $time = $s->start_time ? substr($s->start_time, 0, 5).'–'.substr($s->end_time, 0, 5) : '';
        $title = match ($s->status) {
            ClassSession::STATUS_CENTER_BREAK => 'No class (break)',
            ClassSession::STATUS_CANCELLED    => 'Class cancelled',
            default                           => trim($time.' '.$class->name),
        };

        return [
            'title' => $title,
            'start' => $s->date->format('Y-m-d'),
            'allDay' => true,
            'sortPriority' => 2,
            'classNames' => $classNames,
            'backgroundColor' => $bg,
            'borderColor' => $bg,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'kind' => 'session',
                'status' => $s->status,
                'reason' => $s->reason,
                'attendance' => $attendance[$s->date->format('Y-m-d')] ?? null,
            ],
        ];
    }

    private function calendarEvent(CalendarEvent $e): array
    {
        return [
            'title' => $e->title,
            'start' => $e->start_date->format('Y-m-d'),
            'end' => $e->end_date ? $e->end_date->copy()->addDay()->format('Y-m-d') : null,
            'allDay' => true,
            'sortPriority' => 0,
            'classNames' => ['ev-cal-event'],
            'backgroundColor' => $e->color,
            'borderColor' => $e->color,
            'textColor' => '#ffffff',
            'extendedProps' => ['kind' => 'event', 'description' => $e->description, 'color' => $e->color],
        ];
    }
}
