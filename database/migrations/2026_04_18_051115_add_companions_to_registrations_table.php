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
        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('pengikut_laki')->default(0);
            $table->integer('pengikut_perempuan')->default(0);
            $table->integer('pengikut_anak')->default(0);
            $table->integer('jumlah_pengikut')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['pengikut_laki', 'pengikut_perempuan', 'pengikut_anak', 'jumlah_pengikut']);
        });
    }
};
