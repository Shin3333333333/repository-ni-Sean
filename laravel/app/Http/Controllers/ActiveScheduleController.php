<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActiveSchedule;

class ActiveScheduleController extends Controller
{
    // Get currently active schedule
    public function getActive()
    {
        $active = ActiveSchedule::latest()->first();
        return response()->json($active ?? []);
    }

    // Set a schedule as active
    public function setActive(Request $request)
    {
        $validated = $request->validate([
            'academicYear' => 'required|string',
            'semester' => 'required|string',
        ]);

        // Clear any previous active schedules
        ActiveSchedule::truncate();

        // Save new active one
        $active = ActiveSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Active schedule updated successfully.',
            'data' => $active
        ]);
    }
}
