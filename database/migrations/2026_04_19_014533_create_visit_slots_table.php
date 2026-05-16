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
        Schema::create('visit_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time');
            $table->integer('max_visitors')->default(10);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            // Unique constraint to prevent duplicate slots
            $table->unique(['date', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_slots');
    }
};
