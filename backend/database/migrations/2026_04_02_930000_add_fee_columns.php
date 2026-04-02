<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doctor consultation fee
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)->default(0)->after('specialty');
        });

        // Ward daily bed rate for IPD room charges
        Schema::table('wards', function (Blueprint $table) {
            $table->decimal('daily_rate', 10, 2)->default(0)->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('consultation_fee');
        });
        Schema::table('wards', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};
