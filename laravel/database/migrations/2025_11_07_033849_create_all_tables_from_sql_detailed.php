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
        Schema::create('account_type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
        });

        Schema::create('active_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('academicYear', 50);
            $table->string('semester', 50);
            $table->timestamps();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('curriculums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('year');
            $table->integer('students');
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('professors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('department');
            $table->integer('max_load');
            $table->string('time_unavailable')->nullable();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('type_id')->nullable()->constrained('account_type');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('archived_finalized_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('batch_id');
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
            $table->timestamps();
            $table->index(['academicYear', 'semester'], 'archived_finalized_schedules_academicyear_semester_index');
        });

        Schema::create('finalized_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('batch_id')->nullable();
            $table->bigInteger('faculty_id');
            $table->string('faculty', 100);
            $table->string('subject');
            $table->string('time')->nullable();
            $table->string('classroom')->nullable();
            $table->string('course_code')->nullable();
            $table->string('course_section', 50)->nullable();
            $table->integer('units')->default(0);
            $table->string('academicYear', 20);
            $table->string('semester', 20);
            $table->enum('status', ['pending', 'finalized', 'active', 'staged', 'archived'])->default('pending');
            $table->longText('payload')->nullable();
            $table->index(['user_id', 'batch_id']);
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('pending_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('batch_id')->nullable();
            $table->bigInteger('faculty_id')->nullable();
            $table->string('faculty', 100);
            $table->string('subject');
            $table->string('time')->nullable();
            $table->string('classroom')->nullable();
            $table->string('course_code')->nullable();
            $table->string('course_section', 50)->nullable();
            $table->integer('units')->default(0);
            $table->string('academicYear', 20);
            $table->string('semester', 20);
            $table->enum('status', ['pending', 'finalized'])->default('pending');
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('year_level');
            $table->integer('semester_id')->nullable();
            $table->string('subject_code', 50);
            $table->string('subject_title');
            $table->integer('lec_units')->nullable();
            $table->integer('lab_units')->nullable();
            $table->integer('hours')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->string('error_type');
            $table->text('description');
            $table->text('ai_suggestion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('pending_schedules');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('finalized_schedules');
        Schema::dropIfExists('archived_finalized_schedules');
        Schema::dropIfExists('users');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('professors');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('curriculums');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('active_schedules');
        Schema::dropIfExists('account_type');
    }
};