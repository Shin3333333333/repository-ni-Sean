<?php
Route::post('/upload-curriculum', [CurriculumController::class, 'upload']);
Route::get('/curriculum/{id}', [CurriculumController::class, 'show']);
?>