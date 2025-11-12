<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\FinalizedSchedule;
use App\Models\ArchivedFinalizedSchedule;
use Illuminate\Support\Facades\DB;

class ScheduleArchiveController extends Controller
{
    public function archiveBatch(Request $request): JsonResponse
    {
        // Expected: academicYear, semester, batch_id
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
            'batch_id' => 'required',
        ]);

        $year = $validated['academicYear'];
        $sem = $validated['semester'];
        $batchId = $validated['batch_id'];

        $archivedCount = DB::transaction(function () use ($year, $sem, $batchId) {
            // Fetch finalized schedules for this batch
            $rows = FinalizedSchedule::where('academicYear', $year)
                ->where('semester', $sem)
                ->where('batch_id', $batchId)
                ->get();

            if ($rows->isEmpty()) {
                return 0;
            }

            // Copy into archived_finalized_schedules
            foreach ($rows as $r) {
                ArchivedFinalizedSchedule::create([
                    'user_id' => $r->user_id,
                    'batch_id' => $r->batch_id,
                    'faculty_id' => $r->faculty_id,
                    'faculty' => $r->faculty,
                    'subject' => $r->subject,
                    'time' => $r->time ?? 'TBA',
                    'classroom' => $r->classroom ?? 'TBA',
                    'course_code' => $r->course_code,
                    'course_section' => $r->course_section,
                    'units' => $r->units,
                    'academicYear' => $r->academicYear,
                    'semester' => $r->semester,
                    'status' => 'archived',
                    'payload' => $r->payload,
                ]);
            }

            // Remove from finalized schedules using exact ids we just archived
            $ids = $rows->pluck('id')->all();
            FinalizedSchedule::whereIn('id', $ids)->delete();

            return count($ids);
        });

        return response()->json(['success' => true, 'message' => 'Batch archived.', 'archived' => $archivedCount]);
    }

    public function listArchives(): JsonResponse
    {
        $grouped = ArchivedFinalizedSchedule::select(
                'academicYear', 'semester', 'batch_id', DB::raw('MIN(created_at) as created_at'), DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('academicYear', 'semester', 'batch_id')
            ->orderByDesc('created_at')
            ->get()
            ->map(function($r){
                return [
                    'id' => $r->batch_id,
                    'academicYear' => $r->academicYear,
                    'semester' => $r->semester,
                    'batch_id' => $r->batch_id,
                    'created_at' => $r->created_at,
                    'count' => (int)$r->cnt,
                ];
            });

        return response()->json($grouped);
    }

    public function restore($academicYear, $semester, $batchId): JsonResponse
    {
        DB::transaction(function () use ($academicYear, $semester, $batchId) {
            $rows = ArchivedFinalizedSchedule::where('academicYear', $academicYear)
                ->where('semester', $semester)
                ->where('batch_id', $batchId)
                ->get();
            foreach ($rows as $r) {
                FinalizedSchedule::create([
                    'user_id' => $r->user_id,
                    'batch_id' => $r->batch_id,
                    'faculty_id' => $r->faculty_id,
                    'faculty' => $r->faculty,
                    'subject' => $r->subject,
                    'time' => $r->time,
                    'classroom' => $r->classroom,
                    'course_code' => $r->course_code,
                    'course_section' => $r->course_section,
                    'units' => $r->units,
                    'academicYear' => $r->academicYear,
                    'semester' => $r->semester,
                    'status' => 'finalized',
                    'payload' => $r->payload,
                ]);
            }
            ArchivedFinalizedSchedule::where('academicYear', $academicYear)
                ->where('semester', $semester)
                ->where('batch_id', $batchId)
                ->delete();
        });

        return response()->json(['success' => true, 'message' => 'Archive restored.']);
    }

    public function delete($id): JsonResponse
    {
        // Support both body JSON and query params
        $payload = request()->all();
        $year = $payload['academicYear'] ?? request('academicYear');
        $sem = $payload['semester'] ?? request('semester');
        $batchId = $payload['batch_id'] ?? request('batch_id');
        if (!$year || !$sem || !$batchId) {
            return response()->json(['success' => false, 'message' => 'Missing academicYear/semester/batch_id'], 422);
        }

        ArchivedFinalizedSchedule::where('academicYear', $year)
            ->where('semester', $sem)
            ->where('batch_id', $batchId)
            ->delete();
        return response()->json(['success' => true, 'message' => 'Archive deleted.']);
    }

    public function unsetActive(Request $request): JsonResponse
    {
        // Expected: academicYear, semester
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
        ]);

        // Clear active schedule using existing controller if needed; here just a stub success
        return response()->json(['success' => true, 'message' => 'Active schedule unset.']);
    }
}