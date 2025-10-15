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

            // Redirect Python stderr to a log file so stdout has only JSON
            $stderrLog = storage_path('logs/scheduler_stderr.log');
            $command = 'py ' . escapeshellarg($pythonScriptPath) . ' 2>' . escapeshellarg($stderrLog);

            Log::info('Running command: ' . $command);

            // Execute Python script
            $output = shell_exec($command);

            if ($output === null || trim($output) === '') {
                Log::error('Python script execution failed or returned empty output. Check: ' . $stderrLog);
                return response()->json([
                    'success' => false,
                    'message' => 'Python script execution failed. See log: ' . $stderrLog,
                    'schedule' => []
                ], 500);
            }

            Log::info('Raw Python stdout (first 300 chars): ' . substr($output, 0, 300));

            // Clean invisible characters & trim whitespace
            $cleanOutput = trim($output, "\x00..\x1F\x7F..\xFF \n\r\t");
            $cleanOutput = preg_replace('/^\xEF\xBB\xBF/', '', $cleanOutput);

            Log::info('Cleaned Python stdout (first 300 chars): ' . substr($cleanOutput, 0, 300));

            // Decode JSON safely
            $decoded = json_decode($cleanOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg() . '. See stderr: ' . $stderrLog);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid JSON output from Python script. Check stderr log.',
                    'schedule' => []
                ], 500);
            }

            Log::info('Successfully decoded schedule JSON. Entries: ' . count($decoded['schedule'] ?? []));

            return response()->json([
                'success' => $decoded['success'] ?? true,
                'message' => $decoded['message'] ?? 'Schedule generated successfully!',
                'schedule' => $decoded['schedule'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Schedule generation exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'schedule' => []
            ], 500);
        }
    }
}
