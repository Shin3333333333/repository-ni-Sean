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
            $table->string('name');        // e.g., "BSIT"
            $table->string('section');     // e.g., "3-A"
            $table->string('year');        // "1st", "2nd", etc.
            $table->string('department');  
            $table->integer('students')->default(0);
            $table->string('adviser')->nullable();
            $table->string('curriculum')->nullable();
            $table->string('status')->default('Active'); // Active / Inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
