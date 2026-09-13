<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\ClassSession;
use App\Models\PublicHoliday;
use App\Services\Scheduling\StudentScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The student's schedule: class days, what happens to them, and how the
 * child's attendance has gone. Every figure on the page comes from
 * {@see StudentScheduleService}, so the calendar grid, the "coming up" list
 * and the attendance stat cards can never disagree with one another.
 */
class CalendarController extends Controller
{
    public function __construct(private readonly StudentScheduleService $schedule)
    {
    }

    public function index()
    {
        $studentId = Auth::user()->student_id ?? null;
        $class = $this->schedule->currentClass($studentId);
        $summary = $studentId ? $this->schedule->summary($studentId) : $this->emptySummary();

        return view('student.calendar', [
            'className' => $class?->name,
            'hasClass' => $class !== null,
            'classInfo' => $class,
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'color']),
            'branchColor' => $class?->branch_info?->colorTag() ?? '#16a34a',
            'upcoming' => $studentId ? $this->schedule->upcoming($studentId) : [],
            'summary' => $summary,
            'recentMissed' => $this->recentMissed($summary),
        ]);
    }

    /** Read-only feed: the student's class sessions + their holidays + events. */
    public function events(Request $request): JsonResponse
    {
        $start = substr((string) $request->query('start'), 0, 10);
        $end = substr((string) $request->query('end'), 0, 10);

        if ($start === '' || $end === '') {
            return response()->json([]);
        }

        $studentId = Auth::user()->student_id ?? null;
        $class = $this->schedule->currentClass($studentId);

        $events = $this->schedule
            ->holidayDetails($class, $start, $end)
            ->map(fn (PublicHoliday $h) => $this->holidayEvent($h));

        if ($studentId) {
            $days = $this->schedule->timetable($studentId, $start, $end);

            $events = $events->concat(
                collect($days)->map(fn (array $day) => $this->sessionEvent($day))->values()
            );
        }

        $centreEvents = $this->schedule
            ->events($studentId ?? 0, $class, $start, $end)
            ->map(fn (CalendarEvent $e) => $this->calendarEvent($e));

        return response()->json($events->concat($centreEvents)->values()->all());
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

    /** @param  array<string, mixed>  $day */
    private function sessionEvent(array $day): array
    {
        $bg = match ($day['status']) {
            ClassSession::STATUS_CANCELLED    => '#dc2626',
            ClassSession::STATUS_CENTER_BREAK => '#94a3b8',
            ClassSession::STATUS_HOLIDAY      => '#7c3aed',
            default                           => $day['color'],
        };

        $classNames = ['ev-session', 'ev-session-'.$day['status']];

        if (in_array($day['status'], [ClassSession::STATUS_SCHEDULED, ClassSession::STATUS_COMPLETED], true)) {
            $classNames[] = 'ev-branch-'.$day['branch_id'];
        }

        return [
            'title' => $this->sessionTitle($day),
            'start' => $day['date'],
            'allDay' => true,
            'sortPriority' => 2,
            'classNames' => $classNames,
            'backgroundColor' => $bg,
            'borderColor' => $bg,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'kind' => 'session',
                'status' => $day['status'],
                'reason' => $day['reason'],
                'attendance' => $day['attendance'],
            ],
        ];
    }

    /** @param  array<string, mixed>  $day */
    private function sessionTitle(array $day): string
    {
        if ($day['status'] === ClassSession::STATUS_CENTER_BREAK) {
            return 'No class (break)';
        }

        if ($day['status'] === ClassSession::STATUS_HOLIDAY) {
            return 'No class — '.($day['reason'] ?: 'public holiday');
        }

        if ($day['status'] === ClassSession::STATUS_CANCELLED) {
            return 'Class cancelled';
        }

        return trim($this->timeLabel($day).' '.$day['class']);
    }

    /** @param  array<string, mixed>  $day */
    private function timeLabel(array $day): string
    {
        return $day['start_time']
            ? substr((string) $day['start_time'], 0, 5).'–'.substr((string) $day['end_time'], 0, 5)
            : '';
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

    /**
     * The last few days the child missed or arrived late, newest first.
     *
     * @param  array<string, mixed>  $summary
     * @return array<string, array<string, mixed>>
     */
    private function recentMissed(array $summary): array
    {
        return collect($summary['records'] ?? [])
            ->filter(fn (array $r) => in_array($r['status'], ['absent', 'late'], true))
            ->sortKeysDesc()
            ->take(5)
            ->all();
    }

    /** @return array<string, mixed> */
    private function emptySummary(): array
    {
        return [
            'held' => 0, 'present' => 0, 'late' => 0, 'absent' => 0,
            'unmarked' => 0, 'attended' => 0, 'pct' => null, 'records' => [],
        ];
    }
}
