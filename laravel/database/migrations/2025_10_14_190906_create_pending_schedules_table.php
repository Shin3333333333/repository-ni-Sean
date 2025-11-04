<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // assuming users table exists
            $table->string('faculty', 100);
            $table->string('subject', 255);
            $table->string('time')->nullable();
            $table->string('classroom')->nullable();
            $table->string('course_code')->nullable();
            $table->integer('units')->default(0);
            $table->string('academicYear', 20);
            $table->string('semester', 20);
            $table->enum('status', ['pending', 'finalized'])->default('pending');
            $table->timestamps();

            // Optional: foreign key if you have users table
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_schedules');
    }
};