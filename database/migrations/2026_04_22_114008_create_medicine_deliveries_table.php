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
        Schema::create('medicine_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('registration_id');
            $table->unsignedBigInteger('wbp_id');
            $table->string('medicine_name');
            $table->string('quantity');
            $table->string('dosage');
            $table->enum('approval_status', ['waiting', 'approved', 'rejected'])->default('waiting');
            $table->text('rejection_reason')->nullable();
            $table->enum('delivery_status', ['pending', 'delivered'])->default('pending');
            $table->unsignedBigInteger('officer_id')->nullable(); // Officer who received
            $table->unsignedBigInteger('medical_officer_id')->nullable(); // Medical officer who approved/rejected
            $table->unsignedBigInteger('delivery_officer_id')->nullable(); // Officer who delivered to WBP
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            $table->foreign('wbp_id')->references('id')->on('wbps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_deliveries');
    }
};
