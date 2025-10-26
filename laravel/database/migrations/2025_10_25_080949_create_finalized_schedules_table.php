<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finalized_schedules', function (Blueprint $table) {
            $table->id(); // bigint auto-increment primary key
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('faculty', 100);
            $table->string('subject', 255);
            $table->string('time', 255)->nullable();
            $table->string('classroom', 255)->nullable();
            $table->string('course_code', 255)->nullable();
            $table->string('course_section', 50)->nullable();
            $table->integer('units')->default(0);
            $table->string('academicYear', 20);
            $table->string('semester', 20);
            $table->enum('status', ['pending', 'finalized'])->default('pending');
            $table->longText('payload')->nullable();
            $table->timestamps();

            // Optional: index for faster lookups
            $table->index(['user_id', 'batch_id']);
            $table->index(['academicYear', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finalized_schedules');
    }
};
