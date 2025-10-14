<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    public function generateSchedule(Request $request)
    {
        try {
            $pythonScriptPath = base_path('ai/schedule.py');
            Log::info('Attempting to run Python script: ' . $pythonScriptPath);

            if (!file_exists($pythonScriptPath)) {
                Log::error('Python script not found at: ' . $pythonScriptPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduler script not found',
                    'schedule' => []
                ], 500);
            }

            $command = 'py ' . escapeshellarg($pythonScriptPath) . ' 2>&1';
            Log::info('Running command: ' . $command);

            $output = shell_exec($command);

            if ($output === null || trim($output) === '') {
                Log::error('Python script execution failed or returned empty output');
                return response()->json([
                    'success' => false,
                    'message' => 'Python script execution failed',
                    'schedule' => []
                ], 500);
            }

            Log::info('Raw Python output (first 300 chars): ' . substr($output, 0, 300));

            // Clean invisible characters & trim whitespace
            $cleanOutput = trim($output, "\x00..\x1F\x7F..\xFF \n\r\t");
            $cleanOutput = preg_replace('/^\xEF\xBB\xBF/', '', $cleanOutput);

            Log::info('Cleaned Python output (first 300 chars): ' . substr($cleanOutput, 0, 300));

            // Try decoding JSON
            $decoded = json_decode($cleanOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg());
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON output from Python script',
                    'schedule' => []
                ], 500);
            }

            Log::info('Successfully decoded schedule JSON. Entries: ' . count($decoded['schedule'] ?? []));

            // Return to frontend
            return response()->json([
                'success' => $decoded['success'] ?? true,
                'message' => $decoded['message'] ?? 'Schedule generated successfully!',
                'schedule' => $decoded['schedule'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'schedule' => []
            ], 500);
        }
    }
}
