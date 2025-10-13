<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfessorsController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\SchedulesController;
use App\Http\Controllers\ErrorLogsController;
use App\Http\Controllers\RoomsController;
use App\Http\Controllers\CurriculumController;

/* ----------------- AUTH ----------------- */
// Temporarily remove throttle middleware for testing
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

/* ----------------- RESOURCES ----------------- */
Route::apiResource('professors', ProfessorsController::class);
Route::apiResource('courses', CoursesController::class);
Route::apiResource('subjects', SubjectsController::class);
Route::apiResource('schedules', SchedulesController::class);
Route::apiResource('error-logs', ErrorLogsController::class);
Route::apiResource('rooms', RoomsController::class);



Route::get('/curriculums', [CurriculumController::class, 'index']);
Route::post('/curriculums', [CurriculumController::class, 'store']);
Route::get('/curriculums/{id}/subjects', [CurriculumController::class, 'subjects']);
Route::delete('/curriculums/{id}', [CurriculumController::class, 'destroy']);

Route::post('/courses', [CoursesController::class, 'store']);
// routes/api.php
Route::get('/semesters', function() {
    return \App\Models\Semester::all();
});
Route::apiResource('courses', CoursesController::class);

Route::get('/schedule/data', function () {
    return response()->json([
        'courses' => App\Models\Course::with('subjects')->get(),
        'faculty' => App\Models\Professor::all(),
        'rooms' => App\Models\Room::all(),
        'time_slots' => ['Mon 8-10', 'Mon 10-12', 'Tue 8-10', 'Tue 10-12'] // or from DB if you have one
    ]);
});

