<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Course name or section
            $table->string('year');           // Year level
            $table->integer('students');      // Number of students
            $table->unsignedBigInteger('curriculum_id')->nullable();
            $table->timestamps();

            // Fix: ensure table name matches your actual curriculum table
            if (Schema::hasTable('curriculums')) {
                $table->foreign('curriculum_id')
                      ->references('id')
                      ->on('curriculums')
                      ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
