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
        public function index()
            {
                $batches = \DB::table('pending_schedules')
                    ->select(
                        'batch_id',
                        'academicYear',
                        'semester',
                        \DB::raw('MIN(created_at) as created_at')
                    )
                    ->groupBy('batch_id', 'academicYear', 'semester')
                    ->orderByDesc('created_at')
                    ->get();

                return response()->json(['batches' => $batches]);
            }

public function show($batch_id)
{
    $pending = PendingSchedule::where('batch_id', $batch_id)
        ->where('status', 'pending')
        ->get();

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


public function destroy($batch_id)
{
    PendingSchedule::where('batch_id', $batch_id)->delete();

    return response()->json(['success' => true, 'message' => 'Batch deleted successfully']);
}

public function finalize($batch_id)
{
    $pending = PendingSchedule::where('batch_id', $batch_id)->get();

    if ($pending->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'Batch not found'], 404);
    }

    // Example: Move records to the official schedules table
    foreach ($pending as $p) {
        \DB::table('schedules')->insert([
            'faculty' => $p->faculty,
            'subject' => $p->subject,
            'time' => $p->time,
            'classroom' => $p->classroom,
            'course_code' => $p->course_code,
            'units' => $p->units,
            'academicYear' => $p->academicYear,
            'semester' => $p->semester,
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
    // ✅ Generate unique batch ID once per save
    $batchId = Str::uuid();

    DB::beginTransaction();
    try {
        foreach ($data as $item) {
            $createData = [
                'faculty' => $item['faculty'] ?? 'Unknown',
                'subject' => $item['subject'] ?? 'Untitled',
                'time' => $item['time'] ?? null,
                'classroom' => $item['classroom'] ?? null,
                'course_code' => $item['course_code'] ?? null,
                'course_section' => $item['course_section'] ?? null,
                'units' => $item['units'] ?? 0,
                'academicYear' => $item['academicYear'] ?? 'Unknown',
                'semester' => $item['semester'] ?? '1st Semester',
                'status' => $item['status'] ?? 'pending',
                'user_id' => auth()->id(),
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
            'batch_id' => $batchId
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
public function updateBatch(Request $request, $batchId)
{
    $schedules = $request->input('schedules', []);

    DB::beginTransaction();
    try {
        foreach ($schedules as $sched) {
            if (isset($sched['id'])) {
                $updateData = [
                    'faculty' => $sched['faculty'] ?? DB::raw('faculty'),
                    'subject' => $sched['subject'] ?? DB::raw('subject'),
                    'time' => $sched['time'] ?? DB::raw('time'),
                    'classroom' => $sched['classroom'] ?? DB::raw('classroom'),
                    'course_code' => $sched['course_code'] ?? DB::raw('course_code'),
                    'course_section' => $sched['course_section'] ?? DB::raw('course_section'),
                    'units' => $sched['units'] ?? DB::raw('units'),
                    'academicYear' => $sched['academicYear'] ?? DB::raw('academicYear'),
                    'semester' => $sched['semester'] ?? DB::raw('semester'),
                    'updated_at' => now(),
                ];

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
            }
        }

        DB::commit();
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => 'Server error while updating batch', 'error' => $e->getMessage()], 500);
    }

}


}



