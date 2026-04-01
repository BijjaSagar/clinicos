<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * HIMS — IPD (In-Patient Department) Tables
 * Creates: ipd_admissions, ipd_progress_notes, ipd_vitals,
 *          ipd_medication_orders, ipd_medication_administrations
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_ipd_tables: up');

        // ── IPD Admissions ────────────────────────────────────────────────────
        if (!Schema::hasTable('ipd_admissions')) {
            Schema::create('ipd_admissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained();
                $table->foreignId('bed_id')->constrained('beds');
                $table->foreignId('ward_id')->constrained('wards');
                $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
                $table->foreignId('admitted_by')->constrained('users');
                $table->foreignId('primary_doctor_id')->constrained('users');
                $table->json('consultant_doctor_ids')->nullable();
                $table->string('admission_number')->unique();
                $table->timestamp('admission_date');
                $table->timestamp('discharge_date')->nullable();
                $table->enum('admission_type', [
                    'emergency', 'elective', 'transfer', 'maternity',
                ])->default('elective');
                $table->enum('discharge_type', [
                    'cured', 'lama', 'referred', 'death', 'absconded',
                ])->nullable();
                $table->text('diagnosis_at_admission')->nullable();
                $table->text('final_diagnosis')->nullable();
                $table->json('icd_codes')->nullable();
                $table->boolean('mlc_case')->default(false);
                $table->string('mlc_number')->nullable();
                $table->foreignId('insurance_id')->nullable()->constrained('patient_insurances')->nullOnDelete();
                $table->string('tpa_name')->nullable();
                $table->integer('estimated_days')->nullable();
                $table->integer('actual_days')->nullable(); // computed/updated on discharge
                $table->enum('diet_type', [
                    'normal', 'diabetic', 'low_salt', 'soft', 'liquid', 'npo',
                ])->default('normal');
                $table->enum('status', [
                    'admitted', 'discharged', 'transferred', 'expired',
                ])->default('admitted');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('patient_id');
                $table->index(['clinic_id', 'status']);
                $table->index('admission_number');
                $table->index('bed_id');
                $table->index('ward_id');
            });
            Log::info('create_ipd_tables: ipd_admissions created');
        }

        // ── IPD Progress Notes ────────────────────────────────────────────────
        if (!Schema::hasTable('ipd_progress_notes')) {
            Schema::create('ipd_progress_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->foreignId('recorded_by')->constrained('users');
                $table->date('note_date');
                $table->time('note_time');
                $table->enum('note_type', ['doctor', 'nursing', 'consultant'])->default('doctor');
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();
                $table->text('free_text')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('admission_id');
                $table->index(['admission_id', 'note_date']);
            });
            Log::info('create_ipd_tables: ipd_progress_notes created');
        }

        // ── IPD Vitals ────────────────────────────────────────────────────────
        if (!Schema::hasTable('ipd_vitals')) {
            Schema::create('ipd_vitals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
                $table->foreignId('recorded_by')->constrained('users');
                $table->timestamp('recorded_at');
                $table->decimal('temperature', 4, 1)->nullable();
                $table->enum('temperature_unit', ['C', 'F'])->default('C');
                $table->integer('pulse')->nullable();
                $table->integer('bp_systolic')->nullable();
                $table->integer('bp_diastolic')->nullable();
                $table->integer('respiratory_rate')->nullable();
                $table->decimal('spo2', 4, 1)->nullable();
                $table->decimal('weight_kg', 5, 1)->nullable();
                $table->decimal('blood_glucose', 5, 1)->nullable();
                $table->integer('gcs_score')->nullable();
                $table->integer('pain_score')->nullable(); // 0–10
                $table->integer('urine_output_ml')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('admission_id');
                $table->index(['admission_id', 'recorded_at']);
            });
            Log::info('create_ipd_tables: ipd_vitals created');
        }

        // ── IPD Medication Orders ─────────────────────────────────────────────
        if (!Schema::hasTable('ipd_medication_orders')) {
            Schema::create('ipd_medication_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
                $table->foreignId('prescribed_by')->constrained('users');
                $table->string('drug_name');
                $table->foreignId('drug_id')->nullable()->constrained('indian_drugs')->nullOnDelete();
                $table->enum('route', [
                    'oral', 'iv', 'im', 'sc', 'topical',
                    'sublingual', 'inhalation', 'rectal',
                ])->default('oral');
                $table->string('dosage');
                $table->string('frequency');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_sos')->default(false);
                $table->enum('status', ['active', 'stopped', 'completed'])->default('active');
                $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('stopped_at')->nullable();
                $table->string('stop_reason')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('admission_id');
                $table->index(['admission_id', 'status']);
            });
            Log::info('create_ipd_tables: ipd_medication_orders created');
        }

        // ── IPD Medication Administrations ────────────────────────────────────
        if (!Schema::hasTable('ipd_medication_administrations')) {
            Schema::create('ipd_medication_administrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('ipd_medication_orders')->cascadeOnDelete();
                $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
                $table->foreignId('administered_by')->constrained('users');
                $table->timestamp('administered_at');
                $table->string('dose_given');
                $table->string('route_used')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('not_administered')->default(false);
                $table->string('not_administered_reason')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('order_id');
                $table->index('admission_id');
                $table->index(['admission_id', 'administered_at']);
            });
            Log::info('create_ipd_tables: ipd_medication_administrations created');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_medication_administrations');
        Schema::dropIfExists('ipd_medication_orders');
        Schema::dropIfExists('ipd_vitals');
        Schema::dropIfExists('ipd_progress_notes');
        Schema::dropIfExists('ipd_admissions');
    }
};
