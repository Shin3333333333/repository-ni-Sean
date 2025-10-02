<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            // Add core fields (after id for organization)
            $table->string('subject')->after('id');  // e.g., 'Mathematics' (required)
            $table->time('start_time')->after('subject');  // e.g., '09:00'
            $table->time('end_time')->after('start_time');  // e.g., '10:00'
            $table->string('day')->after('end_time');  // e.g., 'Monday' (enum possible, but string for simplicity)

            // Foreign keys (assume rooms/professors tables exist)
            $table->foreignId('room_id')->nullable()->after('day')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('professor_id')->nullable()->after('room_id')->constrained('professors')->onDelete('cascade');

            // Move timestamps to end if needed (but they're already there)
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['room_id']);  // Drop foreign keys first
            $table->dropForeign(['professor_id']);
            $table->dropColumn(['subject', 'start_time', 'end_time', 'day', 'room_id', 'professor_id']);
        });
    }
};
