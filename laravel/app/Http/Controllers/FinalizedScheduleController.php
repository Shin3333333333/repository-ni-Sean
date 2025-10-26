<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FinalizedSchedule;
use App\Models\PendingSchedule;
use App\Models\ActiveSchedule;

class FinalizedScheduleController extends Controller
{
    /**
     * Finalize (save) a schedule batch
     */
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
                        'academicYear' => $row['academicYear'] ?? $academicYear,
                        'semester' => $row['semester'] ?? $semester,
                        'status' => 'finalized',
                        'user_id' => $userId,
                        'batch_id' => $batchId,
                        'payload' => $row['payload'] ?? null,
                    ]);
                }

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

    /**
     * Get all finalized schedules, or filter by academicYear/semester if provided.
     */
    public function index(Request $request)
    {
        $academicYear = $request->query('academicYear');
        $semester = $request->query('semester');

        $query = FinalizedSchedule::query();

        if ($academicYear && $semester) {
            $query->where('academicYear', $academicYear)
                  ->where('semester', $semester);
        }

        $schedules = $query->orderByDesc('created_at')->get();

        $academicYears = FinalizedSchedule::distinct()->pluck('academicYear')->filter()->values();
        $semesters = FinalizedSchedule::distinct()->pluck('semester')->filter()->values();
        $courses = FinalizedSchedule::distinct()->pluck('course_code')->filter()->values();

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

    /**
     * Set a batch as the active schedule (staging action)
     */
    public function stageActive(Request $request)
    {
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
        ]);

        // Clear any previous active schedule
        ActiveSchedule::truncate();

        // Create new active record
        $active = ActiveSchedule::create([
            'academicYear' => $validated['academicYear'],
            'semester' => $validated['semester'],
        ]);

        // Update all finalized schedules’ status for clarity
        FinalizedSchedule::where('academicYear', $validated['academicYear'])
            ->where('semester', $validated['semester'])
            ->update(['status' => 'active']);

        // Optionally mark others as archived
        FinalizedSchedule::where(function ($q) use ($validated) {
            $q->where('academicYear', '!=', $validated['academicYear'])
              ->orWhere('semester', '!=', $validated['semester']);
        })->update(['status' => 'archived']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule staged and set as active successfully.',
            'data' => $active,
        ]);
    }

    /**
     * Fetch the currently active schedule.
     */
    public function getActive()
    {
        $active = ActiveSchedule::latest()->first();

        if (!$active) {
            return response()->json(['active' => null, 'message' => 'No active schedule found.']);
        }

        $schedules = FinalizedSchedule::where('academicYear', $active->academicYear)
            ->where('semester', $active->semester)
            ->get();

        return response()->json([
            'active' => $active,
            'schedules' => $schedules,
        ]);
    }
}
