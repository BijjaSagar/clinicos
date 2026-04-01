<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Phase-3 completion: referrals, wearable readings, photo consent signature,
 * patient_photos file metadata, optional teleconsult URL on appointments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Migration 2026_03_27_000006_phase3_completion_features: starting');

        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (!Schema::hasColumn('patients', 'photo_consent_signature_path')) {
                    $table->string('photo_consent_signature_path', 500)->nullable()->after('photo_consent_at');
                }
            });
            Log::info('patients: photo_consent_signature_path ensured');
        }

        if (Schema::hasTable('patient_photos')) {
            Schema::table('patient_photos', function (Blueprint $table) {
                if (!Schema::hasColumn('patient_photos', 'file_path')) {
                    $table->string('file_path', 500)->nullable()->after('visit_id');
                }
                if (!Schema::hasColumn('patient_photos', 'file_name')) {
                    $table->string('file_name', 255)->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('patient_photos', 'description')) {
                    $table->string('description', 500)->nullable()->after('body_region');
                }
                if (!Schema::hasColumn('patient_photos', 'body_subregion')) {
                    $table->string('body_subregion', 100)->nullable()->after('body_region');
                }
                if (!Schema::hasColumn('patient_photos', 'pair_id')) {
                    $table->unsignedBigInteger('pair_id')->nullable()->after('photo_type');
                }
                if (!Schema::hasColumn('patient_photos', 'storage_disk')) {
                    $table->string('storage_disk', 32)->default('public')->after('file_path');
                }
            });
            Log::info('patient_photos: file metadata columns ensured');
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'teleconsult_meeting_url')) {
                    $table->string('teleconsult_meeting_url', 1000)->nullable()->after('notes');
                }
            });
            Log::info('appointments: teleconsult_meeting_url ensured');
        }

        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('visit_id')->nullable();
                $table->unsignedBigInteger('from_doctor_id')->nullable();
                $table->string('to_specialty', 120)->nullable();
                $table->string('to_facility_name', 200)->nullable();
                $table->string('to_doctor_name', 200)->nullable();
                $table->string('urgency', 32)->default('routine');
                $table->text('reason')->nullable();
                $table->text('clinical_summary')->nullable();
                $table->string('status', 32)->default('draft');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
                $table->foreign('from_doctor_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['clinic_id', 'status']);
            });
            Log::info('referrals table created');
        }

        if (!Schema::hasTable('wearable_readings')) {
            Schema::create('wearable_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('device_type', 64);
                $table->string('source', 32)->default('csv_import');
                $table->timestamp('recorded_at')->nullable();
                $table->unsignedSmallInteger('systolic')->nullable();
                $table->unsignedSmallInteger('diastolic')->nullable();
                $table->unsignedSmallInteger('heart_rate')->nullable();
                $table->unsignedSmallInteger('glucose_mg_dl')->nullable();
                $table->json('raw')->nullable();
                $table->timestamps();

                $table->index(['clinic_id', 'patient_id']);
                $table->index(['patient_id', 'recorded_at']);
            });
            Log::info('wearable_readings table created');
        }

        if (!Schema::hasTable('abdm_hiu_links')) {
            Schema::create('abdm_hiu_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->string('hip_id', 120)->nullable();
                $table->string('care_context_reference', 200)->nullable();
                $table->string('status', 32)->default('pending');
                $table->json('gateway_payload')->nullable();
                $table->timestamps();

                $table->index(['clinic_id', 'patient_id']);
            });
            Log::info('abdm_hiu_links table created');
        }

        Log::info('Migration 2026_03_27_000006_phase3_completion_features: done');
    }

    public function down(): void
    {
        Schema::dropIfExists('abdm_hiu_links');
        Schema::dropIfExists('wearable_readings');
        Schema::dropIfExists('referrals');

        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'teleconsult_meeting_url')) {
            Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn('teleconsult_meeting_url'));
        }

        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'photo_consent_signature_path')) {
            Schema::table('patients', fn (Blueprint $t) => $t->dropColumn('photo_consent_signature_path'));
        }

        if (Schema::hasTable('patient_photos')) {
            Schema::table('patient_photos', function (Blueprint $table) {
                foreach (['storage_disk', 'pair_id', 'body_subregion', 'description', 'file_name', 'file_path'] as $col) {
                    if (Schema::hasColumn('patient_photos', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
