<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingSchedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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

    return response()->json(['pending' => $pending]);
}public function destroy($batch_id)
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

    foreach ($data as $item) {
        PendingSchedule::create([
            'faculty' => $item['faculty'] ?? 'Unknown',
            'subject' => $item['subject'] ?? 'Untitled',
            'time' => $item['time'] ?? null,
            'classroom' => $item['classroom'] ?? null,
            'course_code' => $item['course_code'] ?? null,
            'units' => $item['units'] ?? 0,
            'academicYear' => $item['academic_year'] ?? 'Unknown',
            'semester' => $item['semester'] ?? '1st Semester',
            'status' => $item['status'] ?? 'pending',
            'user_id' => auth()->id(),
            'batch_id' => $batchId, // ✅ <— This is the important part
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Schedule saved as pending successfully!',
        'batch_id' => $batchId
    ]);
    }
public function updateBatch(Request $request, $batchId)
{
    $schedules = $request->input('schedules', []);

    foreach ($schedules as $sched) {
        if (isset($sched['id'])) {
            DB::table('pending_schedules')
                ->where('id', $sched['id'])
                ->where('batch_id', $batchId)
                ->update([
                    'faculty' => $sched['faculty'] ?? '',
                    'subject' => $sched['subject'] ?? '',
                    'time' => $sched['time'] ?? '',
                    'classroom' => $sched['classroom'] ?? '',
                    'course_code' => $sched['course_code'] ?? '',
                    'units' => $sched['units'] ?? '',
                    'academicYear' => $sched['academicYear'] ?? '',
                    'semester' => $sched['semester'] ?? '',
                    'updated_at' => now(),
                ]);
        }
    }

    return response()->json(['success' => true]);
}


}



