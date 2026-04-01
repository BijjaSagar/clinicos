<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Foundation for ClinicOS → HIMS expansion: facility type, licensed beds, JSON feature flags.
 *
 * @see docs/HIMS_EXPANSION_PLAN.md
 * @see config/hims_expansion.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Migration: add HIMS foundation columns to clinics');

        Schema::table('clinics', function (Blueprint $table) {
            $table->string('facility_type', 40)->default('clinic')->after('plan');
            $table->unsignedSmallInteger('licensed_beds')->nullable()->after('facility_type');
            $table->json('hims_features')->nullable()->after('licensed_beds');
            $table->index('facility_type');
        });
    }

    public function down(): void
    {
        Log::info('Migration: rollback HIMS foundation columns on clinics');

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropIndex(['facility_type']);
            $table->dropColumn(['facility_type', 'licensed_beds', 'hims_features']);
        });
    }
};
