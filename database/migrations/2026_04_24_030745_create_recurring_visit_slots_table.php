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
        Schema::create('recurring_visit_slots', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week'); // 0 (Sunday) to 6 (Saturday)
            $table->string('session_name')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_visitors')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_visit_slots');
    }
};
