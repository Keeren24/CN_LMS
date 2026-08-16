<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassAttendance;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $student_id = Auth::user()->student_id;
        $class_id   = Student::where('id', $student_id)->value('cn_class_id');
        $classInfo  = $class_id ? StudentClass::find($class_id) : null;

        $rows = ClassAttendance::where('student_id', $student_id)
            ->when($class_id, fn($q) => $q->where('class_id', $class_id))
            ->orderBy('date')
            ->get(['date', 'status', 'remarks']);

        // Date-keyed map for FullCalendar JS
        $records = [];
        foreach ($rows as $row) {
            $records[$row->date->format('Y-m-d')] = [
                'status'  => $row->status,
                'remarks' => $row->remarks ?? '',
            ];
        }

        $presentCount  = $rows->where('status', 'present')->count();
        $absentCount   = $rows->where('status', 'absent')->count();
        $lateCount     = $rows->where('status', 'late')->count();
        $totalSessions = $rows->count();
        $attendancePct = $totalSessions > 0
            ? round(($presentCount / $totalSessions) * 100, 1)
            : null;

        $recentMissed = collect($records)
            ->filter(fn($r) => in_array($r['status'], ['absent', 'late']))
            ->sortKeysDesc()
            ->take(5);

        return view('student.attendance', compact(
            'records', 'classInfo',
            'presentCount', 'absentCount', 'lateCount',
            'totalSessions', 'attendancePct', 'recentMissed'
        ));
    }
}
