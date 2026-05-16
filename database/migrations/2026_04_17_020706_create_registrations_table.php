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
        Schema::create('registrations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nik', 16);
            $table->string('visitor_name');
            $table->string('visitor_phone');
            $table->text('visitor_address');
            $table->string('inmate_name');
            $table->string('inmate_number');
            $table->string('relationship');
            $table->date('visit_date');
            $table->string('visit_time');
            $table->string('room_block')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
