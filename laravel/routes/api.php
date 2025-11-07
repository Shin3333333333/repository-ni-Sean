<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Models\Subject;

Route::get('/test-db', function () {
    return response()->json(['subject_count' => Subject::count()]);
});

Route::get('/departments', function () {
    $path = base_path('ai/departments.json');
    if (!File::exists($path)) {
        abort(404);
    }
    $json = File::get($path);
    return response($json, 200, ['Content-Type' => 'application/json']);
});
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
    ProfessorController,
    ArchivedFinalizedScheduleController
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
Route::apiResource('professors', ProfessorsController::class)->middleware('auth:sanctum');
Route::post('/faculty/create-temporary-account', [ProfessorsController::class, 'createTemporaryAccount'])->middleware('auth:sanctum', 'role:admin');
Route::apiResource('courses', CoursesController::class)->middleware('auth:sanctum');
Route::apiResource('subjects', SubjectsController::class)->middleware('auth:sanctum', 'role:admin');
Route::apiResource('schedules', SchedulesController::class)->middleware('auth:sanctum', 'role:admin');
Route::apiResource('error-logs', ErrorLogsController::class)->middleware('auth:sanctum', 'role:admin');
Route::apiResource('rooms', RoomsController::class)->middleware('auth:sanctum');
Route::get('/users', [UserController::class, 'index'])->middleware('auth:sanctum', 'role:admin');
Route::get('/user', [AuthenticatedSessionController::class, 'getAuthenticatedUser'])->middleware('auth:sanctum');
Route::put('/user', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::put('/user/password', [UserController::class, 'updatePassword'])->middleware('auth:sanctum');
Route::put('/professor/details', [ProfessorController::class, 'updateDetails'])->middleware('auth:sanctum', 'role:faculty');
Route::get('/professor/details', [ProfessorController::class, 'getDetails'])->middleware('auth:sanctum', 'role:faculty');
Route::get('/faculty/schedule', [ProfessorController::class, 'getSchedule'])->middleware('auth:sanctum', 'role:faculty');
Route::get('/faculty/{userId}/schedule-history', [ProfessorController::class, 'getScheduleHistory'])->middleware('auth:sanctum', 'role:faculty');

/*
|--------------------------------------------------------------------------
| Curriculum Management
|--------------------------------------------------------------------------
*/
Route::get('/curriculums', [CurriculumController::class, 'index'])->middleware('auth:sanctum', 'role:admin');
Route::post('/curriculums', [CurriculumController::class, 'store'])->middleware('auth:sanctum', 'role:admin');
Route::get('/curriculums/{id}/subjects', [CurriculumController::class, 'subjects'])->middleware('auth:sanctum', 'role:admin');
Route::put('/curriculums/{id}', [CurriculumController::class, 'update'])->middleware('auth:sanctum', 'role:admin');
Route::delete('/curriculums/{id}', [CurriculumController::class, 'destroy'])->middleware('auth:sanctum', 'role:admin');

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
Route::get('/finalized-schedules/active', [FinalizedScheduleController::class, 'getActive']);
// optional
Route::get('/finalized-schedules/{batch_id}', [FinalizedScheduleController::class, 'show']); // optional
Route::delete('/finalized-schedules/{batch_id}', [FinalizedScheduleController::class, 'destroy']); // optional

Route::post('/finalized-schedules/{batch_id}/activate', [FinalizedScheduleController::class, 'activate']);
Route::post('/finalized-schedules/{batch_id}/archive', [FinalizedScheduleController::class, 'archive']);

Route::get('/active-schedules', [ActiveScheduleController::class, 'index']);
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
Route::post('/archives/{academicYear}/{semester}/{batch_id}/restore', [ScheduleArchiveController::class, 'restore']);
Route::delete('/archives/{id}', [ScheduleArchiveController::class, 'delete']);
Route::delete('/archives/{academicYear}/{semester}/{batch_id}', [ArchivedFinalizedScheduleController::class, 'destroy']);
Route::post('/unset-active-schedule', [ScheduleArchiveController::class, 'unsetActive']);