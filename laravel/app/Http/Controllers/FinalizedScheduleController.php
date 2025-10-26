<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FinalizedSchedule;
use App\Models\PendingSchedule;

class FinalizedScheduleController extends Controller
{
    public function saveFinalizedSchedule(Request $request)
    {
        $scheduleArray = $request->input('schedule', []);
        $unassigned = $request->input('unassigned', []);

        if (!empty($unassigned)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot finalize schedule: there are still unassigned subjects.',
            ], 400);
        }

        // ✅ Handle batch ID safely
        $batchId = $request->input('batch_id') ?? ($scheduleArray[0]['batch_id'] ?? null);
        if (!$batchId) {
            return response()->json(['success' => false, 'message' => 'Batch ID not provided.'], 400);
        }

        $userId = Auth::id();
        $academicYear = $request->input('academicYear');
        $semester = $request->input('semester');

        try {
            DB::transaction(function () use ($scheduleArray, $userId, $batchId, $academicYear, $semester) {
                foreach ($scheduleArray as $row) {
                    FinalizedSchedule::create([
                        'faculty' => $row['faculty'] ?? null,
                        'subject' => $row['subject'] ?? null,
                        'time' => $row['time'] ?? null,
                        'classroom' => $row['classroom'] ?? null,
                        'course_code' => $row['course_code'] ?? null,
                        'course_section' => $row['course_section'] ?? null,
                        'units' => $row['units'] ?? 0,
                        // ✅ Guarantee fallback from top-level values
                        'academicYear' => $row['academicYear'] ?? $academicYear,
                        'semester' => $row['semester'] ?? $semester,
                        'status' => 'finalized',
                        'user_id' => $userId,
                        'batch_id' => $batchId,
                        'payload' => $row['payload'] ?? null,
                    ]);
                }

                // ✅ Remove pending records once finalized
                PendingSchedule::where('batch_id', $batchId)->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Schedule finalized successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finalizing schedule: ' . $e->getMessage(),
            ], 500);
        }
    }
// FinalizedScheduleController.php (replace or add this method)
public function index(Request $request)
{
    $academicYear = $request->query('academicYear');
    $semester = $request->query('semester');

    // If specific academicYear+semester provided -> return those schedules
    if ($academicYear && $semester) {
        $schedules = FinalizedSchedule::where('academicYear', $academicYear)
            ->where('semester', $semester)
            ->get();
    } else {
        // Otherwise return ALL (or last N) so the frontend can populate filters
        $schedules = FinalizedSchedule::orderByDesc('created_at')->get();
    }

    // Useful meta for frontend filters (distinct values)
    $academicYears = FinalizedSchedule::query()
        ->whereNotNull('academicYear')
        ->distinct()
        ->pluck('academicYear')
        ->toArray();

    $semesters = FinalizedSchedule::query()
        ->whereNotNull('semester')
        ->distinct()
        ->pluck('semester')
        ->toArray();

    $courses = FinalizedSchedule::query()
        ->whereNotNull('course_code')
        ->distinct()
        ->pluck('course_code')
        ->toArray();

    return response()->json([
        'success' => true,
        'schedules' => $schedules,
        'meta' => [
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'courses' => $courses,
        ],
    ]);
}

}
