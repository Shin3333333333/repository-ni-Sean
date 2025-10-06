<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfessorsController;
use App\Http\Controllers\CoursesController;
use App\Http\Controllers\SubjectsController;
use App\Http\Controllers\SchedulesController;
use App\Http\Controllers\ErrorLogsController;
use App\Http\Controllers\RoomsController;

/* ----------------- AUTH ----------------- */
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

/* ----------------- RESOURCES ----------------- */
Route::apiResource('professors', ProfessorsController::class);
Route::apiResource('courses', CoursesController::class);
Route::apiResource('subjects', SubjectsController::class);
Route::apiResource('schedules', SchedulesController::class);
Route::apiResource('error-logs', ErrorLogsController::class);
Route::apiResource('rooms', RoomsController::class);
