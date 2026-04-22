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
        Schema::create('money_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('registration_id');
            $table->foreign('registration_id')->references('id')->on('registrations')->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'delivered'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('money_deposits');
    }
};
