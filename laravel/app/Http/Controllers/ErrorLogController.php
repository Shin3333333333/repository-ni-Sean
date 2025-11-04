<?php

namespace App\Http\Controllers;

use App\Models\FinalizedSchedule;
use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->input('academic_year');
        $semester = $request->input('semester');

        $academicYears = FinalizedSchedule::distinct()->pluck('academicYear')->toArray();
        $semesters = FinalizedSchedule::distinct()->pluck('semester')->toArray();

        $errors = ErrorLog::when($academicYear, function ($query, $academicYear) {
            return $query->where('academic_year', $academicYear);
        })
        ->when($semester, function ($query, $semester) {
            return $query->where('semester', $semester);
        })
        ->when($request->filled('finalized_schedule'), function ($query) use ($request) {
            return $query->where('finalized_schedule', filter_var($request->input('finalized_schedule'), FILTER_VALIDATE_BOOLEAN));
        })
        ->get();

        return response()->json([
            'errors' => $errors,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
        ]);
    }
}