<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasColumn('pending_schedules', 'payload')) {
            Schema::table('pending_schedules', function (Blueprint $table) {
                $table->json('payload')->nullable()->after('semester');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('pending_schedules', 'payload')) {
            Schema::table('pending_schedules', function (Blueprint $table) {
                $table->dropColumn('payload');
            });
        }
    }
};
