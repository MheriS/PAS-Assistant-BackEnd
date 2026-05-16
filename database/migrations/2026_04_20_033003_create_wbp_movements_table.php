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
        Schema::create('wbp_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wbp_id')->constrained('wbps')->onDelete('cascade');
            $table->enum('type', ['masuk', 'keluar']);
            $table->dateTime('tanggal');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wbp_movements');
    }
};
