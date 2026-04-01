<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * HIMS — Lab Management Tables
 * Creates: lab_departments, lab_tests_catalog, lab_test_panels,
 *          lab_orders, lab_order_items, lab_samples, lab_results
 *
 * NOTE: lab_orders already exists in 2026_03_27_000002_create_lab_orders_table.php
 * (external-provider integration). This migration creates the internal HIMS
 * lab management tables using a different naming convention where needed, and
 * drops/recreates lab_orders only if the old table is absent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_lab_management_tables: up');

        // ── Lab Departments ───────────────────────────────────────────────────
        if (!Schema::hasTable('lab_departments')) {
            Schema::create('lab_departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name'); // e.g. Biochemistry, Haematology, etc.
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'is_active']);
            });
            Log::info('create_lab_management_tables: lab_departments created');
        }

        // ── Lab Tests Catalog ─────────────────────────────────────────────────
        if (!Schema::hasTable('lab_tests_catalog')) {
            Schema::create('lab_tests_catalog', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('lab_departments')->cascadeOnDelete();
                $table->string('test_code');
                $table->string('test_name');
                $table->enum('test_type', ['single', 'panel'])->default('single');
                $table->decimal('price', 10, 2);
                $table->enum('sample_type', [
                    'blood', 'urine', 'stool', 'swab',
                    'fluid', 'tissue', 'sputum', 'other',
                ]);
                $table->string('sample_volume')->nullable();
                $table->string('container_type')->nullable(); // e.g. EDTA, Plain, Fluoride
                $table->json('normal_range_male')->nullable();
                $table->json('normal_range_female')->nullable();
                $table->json('normal_range_child')->nullable();
                $table->string('unit')->nullable();
                $table->integer('tat_hours')->default(24); // Turnaround time
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('department_id');
                $table->index(['clinic_id', 'is_active']);
                $table->index(['clinic_id', 'test_code']);
            });
            Log::info('create_lab_management_tables: lab_tests_catalog created');
        }

        // ── Lab Test Panels (pivot: panel → component tests) ──────────────────
        if (!Schema::hasTable('lab_test_panels')) {
            Schema::create('lab_test_panels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('panel_id')
                    ->constrained('lab_tests_catalog')
                    ->cascadeOnDelete();
                $table->foreignId('test_id')
                    ->constrained('lab_tests_catalog')
                    ->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['panel_id', 'test_id']);
            });
            Log::info('create_lab_management_tables: lab_test_panels created');
        }

        // ── Lab Orders ────────────────────────────────────────────────────────
        // The earlier migration (2026_03_27) created lab_orders for external
        // provider integrations. The HIMS internal lab uses the same table name
        // but with a richer schema. We only create it if it does not yet exist,
        // otherwise we rely on the existing table and its structure.
        if (!Schema::hasTable('lab_orders')) {
            Schema::create('lab_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained();
                $table->foreignId('admission_id')->nullable()->constrained('ipd_admissions')->nullOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->foreignId('ordered_by')->constrained('users');
                $table->string('order_number', 30)->unique();
                $table->date('order_date');
                $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
                $table->enum('status', [
                    'ordered', 'sample_collected', 'processing', 'completed', 'cancelled',
                ])->default('ordered');
                $table->text('clinical_notes')->nullable();
                $table->timestamps();
                $table->index(['clinic_id', 'status']);
                $table->index('patient_id');
                $table->index('order_number');
                $table->index('admission_id');
            });
            Log::info('create_lab_management_tables: lab_orders created');
        } else {
            Log::info('create_lab_management_tables: lab_orders already exists, skip');
        }

        // ── Lab Order Items ───────────────────────────────────────────────────
        if (!Schema::hasTable('lab_order_items')) {
            Schema::create('lab_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('lab_orders')->cascadeOnDelete();
                $table->foreignId('test_id')->constrained('lab_tests_catalog')->cascadeOnDelete();
                $table->decimal('price', 10, 2);
                $table->decimal('discount', 10, 2)->default(0);
                $table->enum('status', [
                    'pending', 'in_progress', 'completed', 'cancelled',
                ])->default('pending');
                $table->timestamps();
                $table->index('order_id');
                $table->index('test_id');
                $table->index(['order_id', 'status']);
            });
            Log::info('create_lab_management_tables: lab_order_items created');
        }

        // ── Lab Samples ───────────────────────────────────────────────────────
        if (!Schema::hasTable('lab_samples')) {
            Schema::create('lab_samples', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('lab_orders')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('lab_order_items')->cascadeOnDelete();
                $table->string('barcode')->unique();
                $table->string('sample_type');
                $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('collected_at')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->string('rejection_reason')->nullable();
                $table->enum('status', [
                    'pending', 'collected', 'received', 'rejected', 'processing',
                ])->default('pending');
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('order_id');
                $table->index('barcode');
                $table->index(['clinic_id', 'status']);
            });
            Log::info('create_lab_management_tables: lab_samples created');
        }

        // ── Lab Results ───────────────────────────────────────────────────────
        if (!Schema::hasTable('lab_results')) {
            Schema::create('lab_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('lab_order_items')->cascadeOnDelete();
                $table->foreignId('sample_id')->nullable()->constrained('lab_samples')->nullOnDelete();
                $table->foreignId('test_id')->constrained('lab_tests_catalog')->cascadeOnDelete();
                $table->text('value');
                $table->string('unit')->nullable();
                $table->string('normal_range')->nullable();
                $table->boolean('is_abnormal')->default(false);
                $table->boolean('is_critical')->default(false);
                $table->timestamp('result_date');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->string('report_url')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('order_item_id');
                $table->index('test_id');
                $table->index(['clinic_id', 'result_date']);
                $table->index(['clinic_id', 'is_critical']);
            });
            Log::info('create_lab_management_tables: lab_results created');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_samples');
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_test_panels');
        Schema::dropIfExists('lab_tests_catalog');
        Schema::dropIfExists('lab_departments');
    }
};
