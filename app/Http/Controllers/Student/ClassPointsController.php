<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassPointsController extends Controller
{
    public function index()
    {
        $student_id = Auth::user()->student_id;
        $class_id   = Student::where('id', $student_id)->value('cn_class_id');
        $classInfo  = $class_id ? StudentClass::find($class_id) : null;

        // All students in the same class ranked by total points
        $rankings = DB::table('class_performances as cp')
            ->join('student as s', 'cp.student_id', '=', 's.id')
            ->where('s.cn_class_id', $class_id)
            ->whereRaw('LOWER(s.status) = ?', ['active'])
            ->select(
                's.id as student_id',
                's.name as student_name',
                DB::raw('SUM(cp.points) as total_points'),
                DB::raw('COUNT(cp.id) as session_count')
            )
            ->groupBy('s.id', 's.name')
            ->orderByDesc('total_points')
            ->get()
            ->values()
            ->map(function ($row, $i) {
                $row->rank = $i + 1;
                return $row;
            });

        // Personal stats
        $myRecord     = $rankings->firstWhere('student_id', $student_id);
        $myRank       = $myRecord->rank ?? null;
        $myPoints     = $myRecord->total_points ?? 0;
        $totalStudents = $rankings->count();

        return view('student.class-points', compact(
            'rankings', 'classInfo',
            'myRank', 'myPoints', 'totalStudents', 'student_id'
        ));
    }
}
