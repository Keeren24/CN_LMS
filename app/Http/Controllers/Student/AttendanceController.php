<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\PublicHoliday;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user      = Auth::user();
        $studentId = $user->student_id;
        $classId   = Student::where('id', $studentId)->value('cn_class_id');
        $className = StudentClass::where('id', $classId)->value('name') ?? '';
        $classInfo = $classId ? StudentClass::find($classId) : null;

        $records = [];

        if ($classId) {
            $rows = ClassAttendance::where('student_id', $studentId)
                ->where('class_id', $classId)
                ->orderBy('date', 'asc')
                ->get(['date', 'status', 'remarks']);

            foreach ($rows as $row) {
                $records[$row->date->format('Y-m-d')] = [
                    'status'  => $row->status,
                    'remarks' => $row->remarks ?? '',
                ];
            }
        }

        $holidays = PublicHoliday::dateNameMap(config('services.holidays.default_state'));

        $recordCollection = collect($records);
        $presentCount     = $recordCollection->where('status', 'present')->count();
        $absentCount      = $recordCollection->where('status', 'absent')->count();
        $lateCount        = $recordCollection->where('status', 'late')->count();
        $totalSessions    = $recordCollection->count();
        $attendancePct    = $totalSessions > 0
            ? round(($presentCount / $totalSessions) * 100, 1)
            : null;

        $recentMissed = $recordCollection
            ->filter(fn($r) => in_array($r['status'], ['absent', 'late']))
            ->sortKeysDesc()
            ->take(5);

        return view('student.attendance', compact(
            'className', 'records', 'holidays',
            'presentCount', 'absentCount', 'lateCount',
            'totalSessions', 'attendancePct',
            'recentMissed', 'classInfo'
        ));
    }
}
