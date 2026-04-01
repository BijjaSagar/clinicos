<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Insurance & TPA Billing Tables
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_insurance_tables: up');

        // Pre-authorization requests
        if (Schema::hasTable('insurance_preauths')) {
            Log::info('create_insurance_tables: insurance_preauths already exists, skip');
        } else {
            Schema::create('insurance_preauths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('tpa_code', 20);
            $table->string('insurance_company', 200);
            $table->string('policy_number', 50);
            $table->string('member_id', 50);
            $table->enum('claim_type', ['cashless', 'reimbursement'])->default('cashless');
            $table->enum('admission_type', ['planned', 'emergency'])->default('planned');
            $table->decimal('estimated_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->json('diagnosis_codes')->nullable();
            $table->json('procedure_codes')->nullable();
            $table->date('admission_date');
            $table->date('expected_discharge')->nullable();
            $table->text('treatment_details');
            $table->enum('status', ['pending', 'approved', 'partially_approved', 'rejected', 'query', 'cancelled'])->default('pending');
            $table->string('preauth_number', 50)->nullable();
            $table->text('tpa_remarks')->nullable();
            $table->text('query_details')->nullable();
            $table->json('documents')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['clinic_id', 'status']);
            $table->index('patient_id');
        });
            Log::info('create_insurance_tables: insurance_preauths created');
        }

        // Insurance claims
        if (Schema::hasTable('insurance_claims')) {
            Log::info('create_insurance_tables: insurance_claims already exists, skip');
        } else {
            Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->unsignedBigInteger('preauth_id')->nullable();
            $table->string('claim_number', 30)->unique();
            $table->string('tpa_code', 20);
            $table->string('insurance_company', 200);
            $table->string('policy_number', 50);
            $table->string('member_id', 50);
            $table->enum('claim_type', ['cashless', 'reimbursement'])->default('cashless');
            $table->decimal('claim_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('settled_amount', 12, 2)->nullable();
            $table->decimal('tds_amount', 10, 2)->nullable();
            $table->decimal('patient_liability', 12, 2)->nullable();
            $table->json('diagnosis_codes')->nullable();
            $table->json('procedure_codes')->nullable();
            $table->date('admission_date')->nullable();
            $table->date('discharge_date');
            $table->text('discharge_summary');
            $table->enum('status', ['pending', 'submitted', 'under_process', 'query', 'approved', 'partially_approved', 'rejected', 'settled', 'closed'])->default('pending');
            $table->string('tpa_claim_id', 50)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('query_details')->nullable();
            $table->date('settlement_date')->nullable();
            $table->string('utr_number', 50)->nullable();
            $table->json('documents')->nullable();
            $table->json('status_history')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['clinic_id', 'status']);
            $table->index('patient_id');
            $table->index('claim_number');
        });
            Log::info('create_insurance_tables: insurance_claims created');
        }

        // TPA configurations for clinic
        if (Schema::hasTable('clinic_tpa_configs')) {
            Log::info('create_insurance_tables: clinic_tpa_configs already exists, skip');
        } else {
            Schema::create('clinic_tpa_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('tpa_code', 20);
            $table->string('tpa_name', 200);
            $table->string('empanelment_id', 50)->nullable();
            $table->string('provider_id', 50)->nullable();
            $table->string('rohini_id', 20)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 15)->nullable();
            $table->string('portal_url', 500)->nullable();
            $table->string('portal_username', 100)->nullable();
            $table->string('portal_password_encrypted', 500)->nullable();
            $table->json('supported_insurers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['clinic_id', 'tpa_code']);
        });
            Log::info('create_insurance_tables: clinic_tpa_configs created');
        }

        Log::info('create_insurance_tables: up complete');
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_tpa_configs');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_preauths');
    }
};
