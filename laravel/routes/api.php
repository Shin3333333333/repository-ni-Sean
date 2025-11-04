<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/departments', function () {
    $path = base_path('ai/departments.json');
    if (!File::exists($path)) {
        abort(404);
    }
    $json = File::get($path);
    return response($json, 200, ['Content-Type' => 'application/json']);
});
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
    ErrorLogController,
    ScheduleArchiveController,
    UserController,
    ProfessorController
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
Route::post('/faculty/create-temporary-account', [ProfessorsController::class, 'createTemporaryAccount']);
Route::apiResource('courses', CoursesController::class);
Route::apiResource('subjects', SubjectsController::class);
Route::apiResource('schedules', SchedulesController::class);
Route::apiResource('error-logs', ErrorLogsController::class);
Route::apiResource('rooms', RoomsController::class);
Route::get('/users', [UserController::class, 'index']);
Route::get('/user', [UserController::class, 'show'])->middleware('auth:sanctum');
Route::put('/user', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::put('/user/password', [UserController::class, 'updatePassword'])->middleware('auth:sanctum');
Route::put('/professor/details', [ProfessorController::class, 'updateDetails'])->middleware('auth:sanctum');
Route::get('/professor/details', [ProfessorController::class, 'getDetails'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Curriculum Management
|--------------------------------------------------------------------------
*/
Route::get('/curriculums', [CurriculumController::class, 'index']);
Route::post('/curriculums', [CurriculumController::class, 'store']);
Route::get('/curriculums/{id}/subjects', [CurriculumController::class, 'subjects']);
Route::put('/curriculums/{id}', [CurriculumController::class, 'update']);
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
Route::post('/save-schedule', [PendingScheduleController::class, 'store'])->middleware('auth:sanctum');
Route::get('/pending-schedules', [PendingScheduleController::class, 'index']);
Route::get('/pending-schedules/{batch_id}', [PendingScheduleController::class, 'show']);
Route::put('/pending-schedules/{batchId}/update', [PendingScheduleController::class, 'updateBatch'])->middleware('auth:sanctum');
Route::post('/pending-schedules/{batch_id}/finalize', [PendingScheduleController::class, 'finalize'])->middleware('auth:sanctum');
Route::delete('/pending-schedules/{batch_id}', [PendingScheduleController::class, 'destroy'])->middleware('auth:sanctum');

// Finalized schedules
Route::post('/finalized-schedules', [FinalizedScheduleController::class, 'saveFinalizedSchedule'])->middleware('auth:sanctum');
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

/*
|--------------------------------------------------------------------------
| Archives (stubs to support frontend)
|--------------------------------------------------------------------------
*/
Route::post('/archive-batch', [ScheduleArchiveController::class, 'archiveBatch']);
Route::get('/archives', [ScheduleArchiveController::class, 'listArchives']);
Route::post('/archives/{id}/restore', [ScheduleArchiveController::class, 'restore']);
Route::delete('/archives/{id}', [ScheduleArchiveController::class, 'delete']);
Route::post('/unset-active-schedule', [ScheduleArchiveController::class, 'unsetActive']);