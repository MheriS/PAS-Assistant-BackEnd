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
        Schema::table('visit_slots', function (Blueprint $table) {
            $table->string('session_name')->nullable()->after('date');
            $table->time('start_time')->nullable()->after('session_name');
            $table->time('end_time')->nullable()->after('start_time');
            
            // Drop unique constraint on date and time
            $table->dropUnique(['date', 'time']);
            $table->dropColumn('time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_slots', function (Blueprint $table) {
            $table->time('time')->nullable()->after('date');
            $table->dropColumn(['session_name', 'start_time', 'end_time']);
            $table->unique(['date', 'time']);
        });
    }
};
