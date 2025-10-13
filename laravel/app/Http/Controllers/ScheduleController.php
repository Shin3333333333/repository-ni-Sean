<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function generateSchedule()
    {
        try {
            $output = shell_exec('python ' . base_path('ai/schedule.py'));
            $schedule = json_decode($output, true);

            if ($schedule === null) {
                return response()->json([
                    'error' => 'Failed to parse schedule JSON',
                    'raw' => $output
                ], 500);
            }

            return response()->json($schedule);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
