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
        Schema::table('visitors', function (Blueprint $table) {
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->nullable()->after('name');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('visitor_gender', ['Laki-laki', 'Perempuan'])->nullable()->after('visitor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('gender');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('visitor_gender');
        });
    }
};
