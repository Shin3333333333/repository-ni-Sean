<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
       Schema::create('error_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
    $table->string('error_type');  // e.g., 'time_conflict'
    $table->text('description');  // e.g., 'End time before start'
    $table->text('ai_suggestion')->nullable();  // For future AI fixes
    $table->timestamps();
});            
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
