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
        Schema::create('wbps', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_regs')->unique();
            $table->string('jenis_kelamin');
            $table->string('perkara');
            $table->string('blok');
            $table->string('kamar');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wbps');
    }
};
