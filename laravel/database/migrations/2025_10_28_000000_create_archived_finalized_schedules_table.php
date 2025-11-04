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
        Schema::create('archived_finalized_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('batch_id')->index();
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->string('faculty')->nullable();
            $table->string('subject');
            $table->string('time');
            $table->string('classroom')->nullable();
            $table->string('course_code')->nullable();
            $table->string('course_section')->nullable();
            $table->integer('units')->default(0);
            $table->string('academicYear');
            $table->string('semester');
            $table->string('status')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps(); // creates created_at and updated_at

            $table->index(['academicYear', 'semester']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_finalized_schedules');
    }
};


