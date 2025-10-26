<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\{
    ProfessorsController,
    CoursesController,
    SubjectsController,
    SchedulesController,
    ErrorLogsController,
    RoomsController,
    CurriculumController,
    ScheduleController,
    PendingScheduleController,
    FinalizedScheduleController,
    ActiveScheduleController,
    ErrorLogController
};

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Core Resources
|--------------------------------------------------------------------------
*/
Route::apiResource('professors', ProfessorsController::class);
Route::apiResource('courses', CoursesController::class);
Route::apiResource('subjects', SubjectsController::class);
Route::apiResource('schedules', SchedulesController::class);
Route::apiResource('error-logs', ErrorLogsController::class);
Route::apiResource('rooms', RoomsController::class);

/*
|--------------------------------------------------------------------------
| Curriculum Management
|--------------------------------------------------------------------------
*/
Route::get('/curriculums', [CurriculumController::class, 'index']);
Route::post('/curriculums', [CurriculumController::class, 'store']);
Route::get('/curriculums/{id}/subjects', [CurriculumController::class, 'subjects']);
Route::delete('/curriculums/{id}', [CurriculumController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Semesters
|--------------------------------------------------------------------------
*/
Route::get('/semesters', fn() => \App\Models\Semester::all());

/*
|--------------------------------------------------------------------------
| Schedule Generation & Pending Management
|--------------------------------------------------------------------------
*/
Route::get('/schedule/data', function () {
    return response()->json([
        'courses' => \App\Models\Course::with('subjects')->get(),
        'faculty' => \App\Models\Professor::all(),
        'rooms' => \App\Models\Room::all(),
        'time_slots' => ['Mon 8-10', 'Mon 10-12', 'Tue 8-10', 'Tue 10-12'],
    ]);
});

Route::post('/generate-schedule', [ScheduleController::class, 'generateSchedule']);
Route::post('/save-schedule', [PendingScheduleController::class, 'store']);
Route::get('/pending-schedules', [PendingScheduleController::class, 'index']);
Route::get('/pending-schedules/{batch_id}', [PendingScheduleController::class, 'show']);
Route::put('/pending-schedules/{batchId}/update', [PendingScheduleController::class, 'updateBatch']);
Route::post('/pending-schedules/{batch_id}/finalize', [PendingScheduleController::class, 'finalize']);
Route::delete('/pending-schedules/{batch_id}', [PendingScheduleController::class, 'destroy']);

// Finalized schedules
Route::post('/finalized-schedules', [FinalizedScheduleController::class, 'saveFinalizedSchedule']);
Route::get('/finalized-schedules', [FinalizedScheduleController::class, 'index']);
Route::post('/finalized-schedules/stage', [FinalizedScheduleController::class, 'stageActive']);
Route::get('/finalized-schedules/active', [FinalizedScheduleController::class, 'getActive']);
// optional
Route::get('/finalized-schedules/{batch_id}', [FinalizedScheduleController::class, 'show']); // optional
Route::delete('/finalized-schedules/{batch_id}', [FinalizedScheduleController::class, 'destroy']); // optional

Route::post('/finalized-schedules/{batch_id}/stage', [FinalizedScheduleController::class, 'stage']);
Route::post('/finalized-schedules/{batch_id}/activate', [FinalizedScheduleController::class, 'activate']);
Route::post('/finalized-schedules/{batch_id}/archive', [FinalizedScheduleController::class, 'archive']);

Route::get('/active-schedule', [ActiveScheduleController::class, 'getActive']);
Route::post('/set-active-schedule', [ActiveScheduleController::class, 'setActive']);
Route::post('/detect-conflicts', [ScheduleController::class, 'detectConflicts']);
Route::get('/errors', [ErrorLogController::class, 'index']);