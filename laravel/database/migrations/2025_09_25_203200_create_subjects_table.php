<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code');      // Subject code
            $table->string('title');     // Subject title
            $table->integer('units');    
            $table->string('semester');  // e.g., "1st" / "2nd"
            $table->string('year_level'); // e.g., "1st Year"
            $table->string('curriculum'); // To link to course curriculum
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
