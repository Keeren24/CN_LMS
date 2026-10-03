<?php

namespace App\Services\Scheduling;

use App\Models\CalendarEvent;
use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\PublicHoliday;
use App\Models\Student;
use App\Models\StudentClass;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * One student's schedule: the class days, what happens to them, and how their
 * attendance has gone. Ported from CN_ADMIN's richer student calendar, minus
 * the class-transfer history and class-plan/curriculum tracking pieces —
 * CN_LMS has no class_enrolments/class_plan_steps tables, so this reads only
 * off the student's current class (student.cn_class_id), same as the rest of
 * the app.
 */
class StudentScheduleService
{
    /** Held sessions with nobody marked are read as present — see summary(). */
    public const UNMARKED_COUNTS_AS_PRESENT = true;

    /** The class the student sits in today, if any. */
    public function currentClass(Student|int|null $student): ?StudentClass
    {
        $studentId = $student instanceof Student ? $student->id : $student;

        if (! $studentId) {
            return null;
        }

        $classId = Student::where('id', $studentId)->value('cn_class_id');

        return $classId ? StudentClass::with('branch_info')->find($classId) : null;
    }

    /**
     * Every class day in the range, keyed by date.
     *
     * @return array<string, array<string, mixed>>
     */
    public function timetable(int $studentId, CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        $class = $this->currentClass($studentId);

        if (! $class) {
            return [];
        }

        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        $sessions = ClassSession::where('student_class_id', $class->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', [
                ClassSession::STATUS_SCHEDULED,
                ClassSession::STATUS_COMPLETED,
                ClassSession::STATUS_CANCELLED,
                ClassSession::STATUS_CENTER_BREAK,
                ClassSession::STATUS_HOLIDAY,
            ])
            ->get();

        $days = [];

        foreach ($sessions as $session) {
            $date = $session->date->format('Y-m-d');
            $days[$date] = $this->day($date, $session, $class);
        }

        $this->attachAttendance($days, $studentId, $from, $to);

        return $days;
    }

    /** Holidays with the detail the calendar needs to colour them. */
    public function holidayDetails(?StudentClass $class, CarbonInterface|string $from, CarbonInterface|string $to): Collection
    {
        return PublicHoliday::query()
            ->whereBetween('date', [Carbon::parse($from)->toDateString(), Carbon::parse($to)->toDateString()])
            ->applicableTo($this->stateFor($class))
            ->get();
    }

    /** Centre events this student can see, in the range. */
    public function events(int $studentId, ?StudentClass $class, CarbonInterface|string $from, CarbonInterface|string $to): Collection
    {
        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        return CalendarEvent::query()
            ->whereDate('start_date', '<=', $toDate)
            ->where(function ($q) use ($fromDate) {
                $q->whereDate('end_date', '>=', $fromDate)
                    ->orWhereDate('start_date', '>=', $fromDate);
            })
            ->where(function ($q) use ($class, $studentId) {
                $q->whereDoesntHave('targets')
                    ->orWhereHas('targets', fn ($t) => $t
                        ->where('target_type', 'class')->where('target_id', $class?->id))
                    ->orWhereHas('targets', fn ($t) => $t
                        ->where('target_type', 'student')->where('target_id', $studentId));
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * The next few class days, for the "coming up" list.
     *
     * Days off are included: a break or a holiday landing on the class day is
     * exactly what a parent is looking for when they check this.
     *
     * @return array<int, array<string, mixed>>
     */
    public function upcoming(int $studentId, int $limit = 6): array
    {
        $today = Carbon::today();
        $days = $this->timetable($studentId, $today, $today->copy()->addWeeks(max($limit, 1) * 2 + 4));

        return collect($days)
            ->filter(fn (array $d) => $d['date'] >= $today->toDateString())
            ->sortKeys()
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * The student's attendance summary against their current class's held sessions.
     *
     * A held session nobody marked is read as present. The centre would rather
     * a child was not shown an absence for a day their tutor simply did not get
     * to; nothing is written to do this — the tutor's list stays accurate and
     * the moment they mark it the real status takes over.
     *
     * @return array<string, mixed>
     */
    public function summary(int $studentId): array
    {
        $empty = [
            'held' => 0, 'present' => 0, 'late' => 0, 'absent' => 0,
            'unmarked' => 0, 'attended' => 0, 'pct' => null, 'records' => [],
        ];

        $class = $this->currentClass($studentId);

        if (! $class) {
            return $empty;
        }

        $today = Carbon::today()->toDateString();

        $held = ClassSession::where('student_class_id', $class->id)
            ->whereDate('date', '<=', $today)
            ->whereIn('status', [ClassSession::STATUS_SCHEDULED, ClassSession::STATUS_COMPLETED])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->unique();

        $records = $this->records($studentId);
        $marked = collect($records);

        $present = $marked->where('status', 'present')->count();
        $late = $marked->where('status', 'late')->count();
        $absent = $marked->where('status', 'absent')->count();

        // Only days the class actually met can be unmarked. A mark on a day
        // with no held session still counts, so the held tally takes in every
        // marked date too.
        $heldDates = $held->merge($marked->keys())->unique();
        $unmarked = $heldDates->reject(fn (string $d) => isset($records[$d]))->count();

        $attended = $present + $unmarked;
        $total = $heldDates->count();

        return [
            'held' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'unmarked' => $unmarked,
            'attended' => $attended,
            'pct' => $total > 0 ? round($attended / $total * 100, 1) : null,
            'records' => $records,
        ];
    }

    /**
     * The student's marks against their current class, keyed by date.
     *
     * @return array<string, array<string, mixed>>
     */
    public function records(int $studentId): array
    {
        $class = $this->currentClass($studentId);

        $rows = ClassAttendance::where('student_id', $studentId)
            ->when($class, fn ($q) => $q->where('class_id', $class->id))
            ->orderBy('date')
            ->get(['date', 'status', 'remarks']);

        $records = [];

        foreach ($rows as $row) {
            $records[$row->date->format('Y-m-d')] = [
                'status' => $row->status,
                'remarks' => $row->remarks ?? '',
            ];
        }

        ksort($records);

        return $records;
    }

    /** @return array<string, mixed> */
    private function day(string $date, ClassSession $session, StudentClass $class): array
    {
        return [
            'date' => $date,
            'status' => $session->status,
            'reason' => $session->reason,
            'class_id' => $class->id,
            'class' => $class->name,
            'branch_id' => $class->branch_id,
            'color' => $class->branch_info?->colorTag() ?? '#16a34a',
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'attendance' => null,
        ];
    }

    /** @param  array<string, array<string, mixed>>  $days */
    private function attachAttendance(array &$days, int $studentId, Carbon $from, Carbon $to): void
    {
        if ($days === []) {
            return;
        }

        $marks = ClassAttendance::where('student_id', $studentId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get(['date', 'status', 'remarks'])
            ->mapWithKeys(fn (ClassAttendance $a) => [
                $a->date->format('Y-m-d') => ['status' => $a->status, 'remarks' => $a->remarks],
            ]);

        foreach ($days as $date => &$day) {
            $day['attendance'] = $marks[$date]['status'] ?? null;
            $day['remarks'] = $marks[$date]['remarks'] ?? null;
        }
    }

    private function stateFor(?StudentClass $class): string
    {
        return $class?->branch_info?->state ?: config('services.holidays.default_state');
    }
}
