<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PendingScheduleController extends Controller
{
    // ✅ List available batches
        // Controller: PendingScheduleController.php
        public function index(Request $request)
            {
                $query = \DB::table('pending_schedules');

                // if user is not admin, only show their own batches
                // Note: Assumes an 'isAdmin' method or attribute on the User model
                if ($request->user() && method_exists($request->user(), 'isAdmin') && !$request->user()->isAdmin()) {
                    $query->where('user_id', $request->user()->id);
                }

                $batches = $query->select(
                        'batch_id',
                        'academicYear',
                        'semester',
                        'user_id', // Include user_id in the selection
                        \DB::raw('MIN(created_at) as created_at')
                    )
                    ->groupBy('batch_id', 'academicYear', 'semester', 'user_id')
                    ->orderBy(DB::raw('MIN(created_at)'), 'desc')
                    ->get();

                return response()->json(['batches' => $batches]);
            }

public function show(Request $request, $batch_id)
{
    $query = PendingSchedule::where('batch_id', $batch_id)
        ->where('status', 'pending');

    // If the user is not an admin, scope the query to their own schedules.
    if ($request->user() && method_exists($request->user(), 'isAdmin') && !$request->user()->isAdmin()) {
        $query->where('user_id', $request->user()->id);
    }

    $pending = $query->get();

    if ($pending->isEmpty()) {
        // Even if the batch exists, if it doesn't belong to the user, treat as not found.
        return response()->json(['grouped' => [], 'unassigned' => []]);
    }

    $grouped = [];
    $unassigned = [];

    foreach ($pending as $p) {
        // Access payload (Laravel auto-casts JSON to array)
        $payload = $p->payload ?? [];

        // Get merged possible assignments from model accessor
        $possibleAssignments = $p->possible_assignments ?? [];
        $possibleCount = count($possibleAssignments);

        // Normalize faculty field
        $faculty = trim($p->faculty ?? '');
        $isUnassigned = false;

        // Detect unassigned based on multiple indicators
        if (
            !$faculty ||
            strtolower($faculty) === 'unknown' ||
            strtolower($faculty) === 'unassigned' ||
            $possibleCount > 0
        ) {
            $isUnassigned = true;
        }

        // ✅ Add academicYear and semester here
        $item = [
            'id' => $p->id,
            'faculty' => $p->faculty,
            'subject' => $p->subject,
            'time' => $p->time,
            'classroom' => $p->classroom,
            'course_code' => $p->course_code,
            'course_section' => $p->course_section,
            'units' => $p->units,
            'academicYear' => $p->academicYear,  // ✅ Added
            'semester' => $p->semester,          // ✅ Added
            'payload' => $payload,
            'possible_assignments' => $possibleAssignments,
            'possible_assignments_count' => $possibleCount,
            '_localId' => $p->id,
        ];

        if ($isUnassigned) {
            $unassigned[] = $item;
        } else {
            if (!isset($grouped[$faculty])) {
                $grouped[$faculty] = [];
            }
            $grouped[$faculty][] = $item;
        }
    }

    return response()->json([
        'grouped' => $grouped,
        'unassigned' => $unassigned,
    ]);
}


public function destroy(Request $request, $batch_id)
{
    $query = PendingSchedule::where('batch_id', $batch_id);

    // If the user is not an admin, ensure they can only delete their own batches.
    if ($request->user() && method_exists($request->user(), 'isAdmin') && !$request->user()->isAdmin()) {
        $query->where('user_id', $request->user()->id);
    }

    $deletedCount = $query->delete();

    if ($deletedCount > 0) {
        return response()->json(['success' => true, 'message' => 'Batch deleted successfully']);
    } else {
        // This can happen if the batch doesn't exist or doesn't belong to the user.
        return response()->json(['success' => false, 'message' => 'Batch not found or access denied'], 404);
    }
}

public function finalize(Request $request, $batch_id)
{
    $query = PendingSchedule::where('batch_id', $batch_id);

    // Scope query to user unless admin
    if ($request->user() && method_exists($request->user(), 'isAdmin') && !$request->user()->isAdmin()) {
        $query->where('user_id', $request->user()->id);
    }

    $pending = $query->get();

    if ($pending->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'Batch not found or access denied'], 404);
    }

    // Example: Move records to the official schedules table
    foreach ($pending as $p) {
        \DB::table('schedules')->insert([
            'faculty' => $p->faculty,
            'faculty_id' => $p->faculty_id,
            'subject' => $p->subject,
            'time' => $p->time,
            'classroom' => $p->classroom,
            'course_code' => $p->course_code,
            'course_section' => $p->course_section,
            'units' => $p->units,
            'academicYear' => $p->academicYear,
            'semester' => $p->semester,
            'user_id' => $p->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Delete pending once finalized
    PendingSchedule::where('batch_id', $batch_id)->delete();

    return response()->json(['success' => true, 'message' => 'Batch finalized successfully']);
}


    // ✅ Save with unique batch
   public function store(Request $request)
{
    $data = $request->input('schedule');
    if (!$data || !is_array($data)) {
        return response()->json(['success' => false, 'message' => 'Invalid data'], 400);
    }
    
    $batchId = Str::uuid();
    $userId = $request->input('user_id', Auth::id());

    // Derive base academicYear and semester from first row
    $baseYear = $data[0]['academicYear'] ?? 'Unknown';
    $semester = $data[0]['semester'] ?? '1st Semester';

    // Compute unique academicYear with suffix for this semester
    $finalAcademicYear = $this->nextUniqueAcademicYear($baseYear, $semester);

    DB::beginTransaction();
    try {
        foreach ($data as $item) {
            $createData = [
                'faculty' => $item['faculty'] ?? 'Unknown',
                'faculty_id' => $item['faculty_id'] ?? null,
                'subject' => $item['subject'] ?? 'Untitled',
                'time' => $item['time'] ?? null,
                'classroom' => $item['classroom'] ?? null,
                'course_code' => $item['course_code'] ?? null,
                'course_section' => $item['course_section'] ?? null,
                'units' => $item['units'] ?? 0,
                'academicYear' => $finalAcademicYear,
                'semester' => $semester,
                'status' => $item['status'] ?? 'pending',
                'user_id' => $userId,
                'batch_id' => $batchId,
            ];

            // only write payload if the DB column exists to avoid SQL errors
            if (isset($item['payload']) && Schema::hasColumn('pending_schedules', 'payload')) {
                $createData['payload'] = $item['payload'];
            }

            PendingSchedule::create($createData);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Schedule saved as pending successfully!',
            'batch_id' => $batchId,
            'academicYear' => $finalAcademicYear,
            'semester' => $semester,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        // return JSON error to avoid HTML 500 page and help debugging
        return response()->json([
            'success' => false,
            'message' => 'Server error while saving pending schedule',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Compute a unique academicYear label for the given semester by appending (n)
 */
private function nextUniqueAcademicYear(string $baseYear, string $semester): string
{
    $existing = DB::table('pending_schedules')
        ->where('semester', $semester)
        ->where('academicYear', 'like', $baseYear . '%')
        ->pluck('academicYear')
        ->toArray();

    if (empty($existing)) {
        return $baseYear;
    }

    $maxSuffix = 0;
    foreach ($existing as $label) {
        if ($label === $baseYear) {
            $maxSuffix = max($maxSuffix, 0);
            continue;
        }
        // match baseYear(n)
        $pattern = '/^' . preg_quote($baseYear, '\/') . '\\((\d+)\\)$/';
        if (preg_match($pattern, $label, $m)) {
            $n = intval($m[1]);
            if ($n > $maxSuffix) $maxSuffix = $n;
        }
    }

    return $baseYear . '(' . ($maxSuffix + 1) . ')';
}
public function updateBatch(Request $request, $batchId)
{
    $schedules = $request->input('schedules', []);
    $deletedIds = array_filter(array_map('intval', $request->input('deleted_ids', [])));
    $userId = $request->input('user_id', auth()->id()); // Get user_id from request or fallback to authenticated user

    DB::beginTransaction();
    try {
        foreach ($schedules as $sched) {
            if (isset($sched['id'])) {
                // Build update set conservatively to avoid touching missing columns and avoid nulling NOT NULL columns
                $updateData = [];

                // Required, non-nullable columns: preserve existing value if client sends null or omits
                if (Schema::hasColumn('pending_schedules', 'faculty')) {
                    $updateData['faculty'] = (array_key_exists('faculty', $sched) && $sched['faculty'] !== null)
                        ? $sched['faculty']
                        : DB::raw('faculty');
                }
                if (Schema::hasColumn('pending_schedules', 'subject')) {
                    $updateData['subject'] = (array_key_exists('subject', $sched) && $sched['subject'] !== null)
                        ? $sched['subject']
                        : DB::raw('subject');
                }
                if (Schema::hasColumn('pending_schedules', 'academicYear')) {
                    $updateData['academicYear'] = (array_key_exists('academicYear', $sched) && $sched['academicYear'] !== null)
                        ? $sched['academicYear']
                        : DB::raw('academicYear');
                }
                if (Schema::hasColumn('pending_schedules', 'semester')) {
                    $updateData['semester'] = (array_key_exists('semester', $sched) && $sched['semester'] !== null)
                        ? $sched['semester']
                        : DB::raw('semester');
                }
                if (Schema::hasColumn('pending_schedules', 'units')) {
                    $updateData['units'] = (array_key_exists('units', $sched) && $sched['units'] !== null)
                        ? $sched['units']
                        : DB::raw('units');
                }

                // Optional/nullable columns: allow setting to null if explicitly provided; otherwise preserve existing
                foreach (['time', 'classroom', 'course_code', 'course_section'] as $col) {
                    if (Schema::hasColumn('pending_schedules', $col)) {
                        if (array_key_exists($col, $sched)) {
                            $updateData[$col] = $sched[$col]; // may be null, which is allowed for these columns
                        } else {
                            $updateData[$col] = DB::raw($col);
                        }
                    }
                }

                // Optional foreign key: only include if column exists
                if (Schema::hasColumn('pending_schedules', 'faculty_id')) {
                    if (array_key_exists('faculty_id', $sched)) {
                        $updateData['faculty_id'] = $sched['faculty_id']; // allow null if client intends to clear
                    }
                }

                $updateData['updated_at'] = now();
                $updateData['user_id'] = $userId;

                // If frontend included possible_assignments or payload, persist it to payload column if present
                if (Schema::hasColumn('pending_schedules', 'payload')) {
                    $existing = DB::table('pending_schedules')->where('id', $sched['id'])->value('payload');
                    $existingPayload = is_string($existing) ? json_decode($existing, true) : ($existing ?? []);

                    if (isset($sched['possible_assignments'])) {
                        $existingPayload['possible_assignments'] = $sched['possible_assignments'];
                    }

                    if (isset($sched['payload']) && is_array($sched['payload'])) {
                        $existingPayload = array_merge($existingPayload, $sched['payload']);
                    }

                    $updateData['payload'] = json_encode($existingPayload);
                }

                DB::table('pending_schedules')
                    ->where('id', $sched['id'])
                    ->where('batch_id', $batchId)
                    ->update($updateData);
            } else {
                // Insert new record into this batch
                $createData = [
                    'faculty' => $sched['faculty'] ?? 'Unknown',
                    'subject' => $sched['subject'] ?? 'Untitled',
                    'units' => $sched['units'] ?? 0,
                    'academicYear' => $sched['academicYear'] ?? (DB::table('pending_schedules')->where('batch_id', $batchId)->value('academicYear') ?? 'Unknown'),
                    'semester' => $sched['semester'] ?? (DB::table('pending_schedules')->where('batch_id', $batchId)->value('semester') ?? '1st Semester'),
                    'status' => 'pending',
                    'batch_id' => $batchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'user_id' => $userId,
                ];

                // Optional/nullable columns
                foreach (['time', 'classroom', 'course_code', 'course_section'] as $col) {
                    if (Schema::hasColumn('pending_schedules', $col)) {
                        $createData[$col] = $sched[$col] ?? null; // allow nulls
                    }
                }
                // Optional foreign key and user
                if (Schema::hasColumn('pending_schedules', 'faculty_id') && array_key_exists('faculty_id', $sched)) {
                    $createData['faculty_id'] = $sched['faculty_id'];
                }
                if (Schema::hasColumn('pending_schedules', 'user_id')) {
                    $createData['user_id'] = $userId;
                }

                if (Schema::hasColumn('pending_schedules', 'payload') && isset($sched['payload'])) {
                    $createData['payload'] = json_encode($sched['payload']);
                }

                DB::table('pending_schedules')->insert($createData);
            }
        }

        // Delete selected rows, if provided
        if (!empty($deletedIds)) {
            DB::table('pending_schedules')
                ->where('batch_id', $batchId)
                ->whereIn('id', $deletedIds)
                ->delete();
        }

        DB::commit();
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Server error while updating batch', 'error' => $e->getMessage()], 500);
    }

}


}