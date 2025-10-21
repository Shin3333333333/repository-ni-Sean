<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    public function generateSchedule(Request $request)
    {
        try {
            ini_set('max_execution_time', 300); // 5 minutes
            ini_set('memory_limit', '512M'); 
            $pythonScriptPath = base_path('ai/newtry.py');
            Log::info('Attempting to run Python script: ' . $pythonScriptPath);

            if (!file_exists($pythonScriptPath)) {
                Log::error('Python script not found at: ' . $pythonScriptPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduler script not found',
                    'schedule' => []
                ], 500);
            }

            // Get input values from frontend
            $academicYear = $request->input('academic_year');
            $semester = $request->input('semester');
            Log::info("Received parameters → Academic Year: {$academicYear}, Semester: {$semester}");

            // Map semester string to ID
            $semesterMap = ['1st Semester' => 1, '2nd Semester' => 2];
            $semesterId = $semesterMap[$semester] ?? 1; // default to 1

            // Define stderr log path BEFORE using it
            $stderrLog = storage_path('logs/scheduler_stderr.log');

            // Build Python command
            $command = sprintf(
                'py %s --academic_year=%s --semester=%d 2>%s',
                escapeshellarg($pythonScriptPath),
                escapeshellarg($academicYear ?? ''),
                $semesterId,
                escapeshellarg($stderrLog)
            );

            Log::info('Running command: ' . $command);
            Log::info('Python script path exists? ' . (file_exists($pythonScriptPath) ? 'YES' : 'NO'));
            Log::info('Command to run: ' . $command);

            // Run the Python script
            $output = shell_exec($command);
            if (strpos($output, '<!DOCTYPE html>') !== false) {
    Log::error('HTML output detected — probably a timeout or error page.');
    return response()->json([
        'success' => false,
        'message' => 'Internal server error: HTML output detected (check Laravel logs).',
        'schedule' => []
    ], 500);
}
            if ($output === null || trim($output) === '') {
                Log::error('Python script execution failed or returned empty output. Check: ' . $stderrLog);
                return response()->json([
                    'success' => false,
                    'message' => 'Python script execution failed. See log: ' . $stderrLog,
                    'schedule' => []
                ], 500);
            }

            Log::info('Raw Python stdout (first 300 chars): ' . substr($output, 0, 300));

            // Clean invisible characters and BOM
            $cleanOutput = trim($output, "\x00..\x1F\x7F..\xFF \n\r\t");
            $cleanOutput = preg_replace('/^\xEF\xBB\xBF/', '', $cleanOutput);

            Log::info('Cleaned Python stdout (first 300 chars): ' . substr($cleanOutput, 0, 300));

            // Decode JSON
            $decoded = json_decode($cleanOutput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg() . '. Check stderr: ' . $stderrLog);
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
                'schedule' => $decoded['schedule'] ?? [],
                'unassigned' => $decoded['unassigned'] ?? [],
                'stats' => [
                    'total_subjects' => $decoded['stats']['total_subjects'] ?? 0,
                    'assigned' => $decoded['stats']['assigned'] ?? 0,
                    'unassigned' => $decoded['stats']['unassigned'] ?? 0,
                ],

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
