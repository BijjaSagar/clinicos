<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Fix lab_orders so the HIMS internal lab module can work alongside
 * the external-lab integration columns that were created in the first migration.
 *
 * Changes:
 *  - Make provider, provider_name, tests, total_amount nullable
 *  - Add HIMS-specific columns if absent: order_date, priority, clinical_notes
 *  - Add 'ordered' to the status enum if absent
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('fix_lab_orders_for_hims: up');

        Schema::table('lab_orders', function (Blueprint $table) {
            // Make external-lab columns nullable so HIMS orders don't need them
            if (Schema::hasColumn('lab_orders', 'provider')) {
                $table->string('provider', 50)->nullable()->change();
            }
            if (Schema::hasColumn('lab_orders', 'provider_name')) {
                $table->string('provider_name', 100)->nullable()->change();
            }
            if (Schema::hasColumn('lab_orders', 'tests')) {
                $table->json('tests')->nullable()->change();
            }
            if (Schema::hasColumn('lab_orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->change();
            }

            // Add HIMS columns if they don't already exist
            if (!Schema::hasColumn('lab_orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('lab_orders', 'priority')) {
                $table->string('priority', 20)->nullable()->default('routine')->after('order_date');
            }
            if (!Schema::hasColumn('lab_orders', 'clinical_notes')) {
                $table->text('clinical_notes')->nullable()->after('priority');
            }
        });

        // Extend the status enum to include 'ordered' if the column is an enum
        // MariaDB/MySQL: use a raw ALTER to add 'ordered' to the enum
        try {
            $currentType = DB::select("
                SELECT COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'lab_orders'
                  AND COLUMN_NAME = 'status'
            ")[0]->COLUMN_TYPE ?? '';

            if (str_contains($currentType, 'enum') && !str_contains($currentType, "'ordered'")) {
                $newType = str_replace("'pending'", "'pending','ordered'", $currentType);
                DB::statement("ALTER TABLE lab_orders MODIFY COLUMN status {$newType} DEFAULT 'pending'");
                Log::info('fix_lab_orders_for_hims: status enum extended with ordered');
            }
        } catch (\Throwable $e) {
            Log::warning('fix_lab_orders_for_hims: could not extend status enum', ['err' => $e->getMessage()]);
        }

        Log::info('fix_lab_orders_for_hims: done');
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            foreach (['order_date', 'priority', 'clinical_notes'] as $col) {
                if (Schema::hasColumn('lab_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
