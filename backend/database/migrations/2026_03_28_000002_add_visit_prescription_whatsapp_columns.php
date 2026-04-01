<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('add_visit_prescription_whatsapp_columns: up');

        if (! Schema::hasTable('visits')) {
            Log::warning('add_visit_prescription_whatsapp_columns: visits table missing, skip');

            return;
        }

        if (! Schema::hasColumn('visits', 'prescription_sent_whatsapp')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->boolean('prescription_sent_whatsapp')->default(false);
            });
            Log::info('add_visit_prescription_whatsapp_columns: prescription_sent_whatsapp added');
        }
        if (! Schema::hasColumn('visits', 'prescription_sent_at')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->timestamp('prescription_sent_at')->nullable();
            });
            Log::info('add_visit_prescription_whatsapp_columns: prescription_sent_at added');
        }

        Log::info('add_visit_prescription_whatsapp_columns: up complete');
    }

    public function down(): void
    {
        Log::info('add_visit_prescription_whatsapp_columns: down');

        if (! Schema::hasTable('visits')) {
            return;
        }

        Schema::table('visits', function (Blueprint $table) {
            if (Schema::hasColumn('visits', 'prescription_sent_at')) {
                $table->dropColumn('prescription_sent_at');
            }
            if (Schema::hasColumn('visits', 'prescription_sent_whatsapp')) {
                $table->dropColumn('prescription_sent_whatsapp');
            }
        });
    }
};
