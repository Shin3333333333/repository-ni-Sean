<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\FinalizedSchedule;
use App\Models\PendingSchedule;
use App\Models\Professor;
use App\Models\ActiveSchedule;
use App\Models\ArchivedFinalizedSchedule;
use Illuminate\Support\Facades\Log;

class FinalizedScheduleController extends Controller
{
    /**
     * Finalize (save) a schedule batch
     */
    public function saveFinalizedSchedule(Request $request)
    {
        try {
            $scheduleArray = $request->input('schedule', []);

            if (empty($scheduleArray)) {
                return response()->json(['success' => false, 'message' => 'Cannot finalize an empty schedule.'], 400);
            }

            $batchId = $request->input('batch_id') ?? ($scheduleArray[0]['batch_id'] ?? null);
            if (!$batchId) {
                return response()->json(['success' => false, 'message' => 'Batch ID not provided.'], 400);
            }

            $userId = Auth::id();
            $baseYear = $request->input('academicYear') ?? ($scheduleArray[0]['academicYear'] ?? null);
            $semester = $request->input('semester') ?? ($scheduleArray[0]['semester'] ?? null);
            if (!$baseYear || !$semester) {
                return response()->json(['success' => false, 'message' => 'Missing academicYear or semester.'], 400);
            }

            $existingBatch = FinalizedSchedule::where('batch_id', $batchId)->first();
            $finalAcademicYear = $existingBatch ? $existingBatch->academicYear : $this->nextUniqueAcademicYear($baseYear, $semester);

            DB::transaction(function () use ($scheduleArray, $userId, $batchId, $finalAcademicYear, $semester) {
                foreach ($scheduleArray as $row) {

                    $facultyId = $row['faculty_id'] ?? null;
                    if (!$facultyId && !empty($row['faculty'])) {
                        $professor = Professor::where('name', $row['faculty'])->first();
                        if ($professor) {
                            $facultyId = $professor->id;
                        }
                    }

                    $facultyName = $row['faculty'] ?? null;
                    if (!$facultyId) {
                        $facultyName = 'TBA';
                    }

                    FinalizedSchedule::updateOrCreate(
                        [
                            'batch_id' => $batchId,
                            'course_code' => $row['course_code'],
                            'course_section' => $row['course_section'],
                        ],
                        [
                            'faculty' => $facultyName,
                            'faculty_id' => $facultyId,
                            'subject' => $row['subject'] ?? null,
                            'time' => !empty($row['time']) ? $row['time'] : 'TBA',
                            'classroom' => !empty($row['classroom']) ? $row['classroom'] : 'TBA',
                            'units' => $row['units'] ?? 0,
                            'academicYear' => $finalAcademicYear,
                            'semester' => $semester,
                            'status' => 'finalized',
                            'user_id' => $userId,
                            'payload' => $row['payload'] ?? null,
                        ]
                    );
                }

                // Delete only the assigned schedules from the pending_schedules table
                $assignedScheduleIds = collect($scheduleArray)->pluck('id')->filter()->all();
                if (!empty($assignedScheduleIds)) {
                    PendingSchedule::whereIn('id', $assignedScheduleIds)->delete();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Schedule finalized successfully!',
                'batch_id' => $batchId,
                'academicYear' => $finalAcademicYear,
                'semester' => $semester,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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
     * Compute unique academicYear label for finalized table
     */
    private function nextUniqueAcademicYear(string $baseYear, string $semester): string
    {
        $existing = DB::table('finalized_schedules')
            ->where('semester', $semester)
            ->where('academicYear', 'like', $baseYear . '%')
            ->pluck('academicYear')
            ->toArray();

        if (empty($existing)) return $baseYear;

        $maxSuffix = 0;
        foreach ($existing as $label) {
            if ($label === $baseYear) { $maxSuffix = max($maxSuffix, 0); continue; }
            $pattern = '/^' . preg_quote($baseYear, '\/') . '\\((\d+)\\)$/';
            if (preg_match($pattern, $label, $m)) {
                $n = intval($m[1]);
                if ($n > $maxSuffix) $maxSuffix = $n;
            }
        }
        return $baseYear . '(' . ($maxSuffix + 1) . ')';
    }

    /**
     * Set a batch as the active schedule (staging action)
     */
    public function stageActive(Request $request)
    {
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
            'batch_id' => 'required|string',
        ]);

        $batchId = $validated['batch_id'];

        // Find the schedule to activate to make sure it exists
        $scheduleToActivate = FinalizedSchedule::where('batch_id', $batchId)->first();

        if (!$scheduleToActivate) {
            return response()->json(['success' => false, 'message' => 'Schedule to be activated not found.'], 404);
        }

        try {
            DB::transaction(function () use ($batchId, $scheduleToActivate) {
                // Find the currently active schedule
                $currentActive = ActiveSchedule::latest()->first();

                if ($currentActive) {
                    // Prevent re-archiving the same batch
                    if ($currentActive->batch_id === $batchId) {
                        return;
                    }
                    
                    // 1. Find all schedules belonging to the currently active batch
                    $schedulesToArchive = FinalizedSchedule::where('batch_id', $currentActive->batch_id)->get();

                    if ($schedulesToArchive->isNotEmpty()) {
                        // 2. Copy them to the archive table
                        foreach ($schedulesToArchive as $schedule) {
                            ArchivedFinalizedSchedule::create([
                                'user_id' => $schedule->user_id,
                                'batch_id' => $schedule->batch_id,
                                'faculty_id' => $schedule->faculty_id,
                                'faculty' => $schedule->faculty,
                                'subject' => $schedule->subject,
                                'time' => $schedule->time,
                                'classroom' => $schedule->classroom,
                                'course_code' => $schedule->course_code,
                                'course_section' => $schedule->course_section,
                                'units' => $schedule->units,
                                'academicYear' => $schedule->academicYear,
                                'semester' => $schedule->semester,
                                'status' => 'archived', // Explicitly set status
                                'payload' => $schedule->payload,
                                'created_at' => $schedule->created_at, // Preserve original creation time
                                'updated_at' => $schedule->updated_at, // Preserve original update time
                            ]);
                        }

                        // 3. Delete them from the finalized_schedules table
                        FinalizedSchedule::where('batch_id', $currentActive->batch_id)->delete();
                    }
                }

                // 4. Truncate the active schedule table to ensure only one is active
                ActiveSchedule::truncate();

                // 5. Create the new active schedule entry for the new batch
                ActiveSchedule::create([
                    'academicYear' => $scheduleToActivate->academicYear,
                    'semester' => $scheduleToActivate->semester,
                    'batch_id' => $batchId,
                    'staged_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Schedule staged successfully.']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error staging schedule: ' . $e->getMessage(),
            ], 500);
        }
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

    public function getFilterOptions()
    {
        $academicYears = FinalizedSchedule::distinct()->pluck('academicYear')->filter()->values();
        $semesters = FinalizedSchedule::distinct()->pluck('semester')->filter()->values();

        return response()->json([
            'academicYears' => $academicYears,
            'semesters' => $semesters,
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = FinalizedSchedule::find($id);

        if (!$schedule) {
            return response()->json(['success' => false, 'message' => 'Schedule not found'], 404);
        }

        $validatedData = $request->validate([
            'faculty' => 'nullable|string',
            'time' => 'nullable|string',
            'classroom' => 'nullable|string',
        ]);

        if (empty($validatedData['time'])) {
            $validatedData['time'] = 'TBA';
        }

        if (empty($validatedData['classroom'])) {
            $validatedData['classroom'] = 'TBA';
        }

        $schedule->update($validatedData);

        if (is_null($schedule->faculty_id)) {
            $schedule->faculty = 'TBA';
            $schedule->save();
        }

        return response()->json(['success' => true, 'message' => 'Schedule updated successfully', 'schedule' => $schedule]);
    }
}