<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActiveSchedule;
use Illuminate\Support\Carbon;

class ActiveScheduleController extends Controller
{
    // Get currently active schedule (normalized response)
    public function getActive()
    {
        $active = ActiveSchedule::latest()->first();
        if (!$active) {
            return response()->json([]);
        }

        return response()->json([
            'academicYear' => $active->academicYear ?? $active->academic_year ?? null,
            'semester' => $active->semester ?? null,
            'batch_id' => $active->batch_id ?? null,
            'staged_at' => ($active->staged_at ?? $active->created_at) ? ($active->staged_at ?? $active->created_at)->toDateTimeString() : null,
        ]);
    }

    // Set a schedule as active (accepts batch_id)
    public function setActive(Request $request)
    {
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
            'batch_id' => 'nullable|string',
        ]);

        // Clear any previous active schedules
        ActiveSchedule::truncate();

        // Save new active one (handles camelCase or snake_case columns)
        $active = new ActiveSchedule();
        // Try to support both column styles depending on your migration/model
        if (isset($active->academic_year)) {
            $active->academic_year = $validated['academicYear'];
        } else {
            $active->academicYear = $validated['academicYear'];
        }
        $active->semester = $validated['semester'];
        if (isset($validated['batch_id'])) {
            $active->batch_id = $validated['batch_id'];
        }
        if (property_exists($active, 'staged_at')) {
            $active->staged_at = now();
        }
        $active->save();

        return response()->json([
            'success' => true,
            'message' => 'Active schedule updated successfully.',
            'data' => [
                'academicYear' => $active->academicYear ?? $active->academic_year ?? null,
                'semester' => $active->semester ?? null,
                'batch_id' => $active->batch_id ?? null,
                'staged_at' => ($active->staged_at ?? $active->created_at) ? ($active->staged_at ?? $active->created_at)->toDateTimeString() : null,
            ],
        ]);
    }
}
