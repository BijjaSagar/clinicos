<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * HIMS — Hospital Structure Tables
 * Creates: wards, rooms, beds, hospital_settings
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_hospital_structure_tables: up');

        // ── Wards ─────────────────────────────────────────────────────────────
        if (!Schema::hasTable('wards')) {
            Schema::create('wards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('ward_type', [
                    'general', 'icu', 'nicu', 'picu', 'maternity',
                    'surgical', 'medical', 'orthopedic', 'pediatric',
                    'emergency', 'private', 'semi_private',
                ])->default('general');
                $table->string('floor')->nullable();
                $table->integer('total_beds')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'ward_type']);
            });
            Log::info('create_hospital_structure_tables: wards created');
        }

        // ── Rooms ─────────────────────────────────────────────────────────────
        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
                $table->string('room_number');
                $table->enum('room_type', [
                    'general', 'private', 'semi_private', 'icu', 'isolation',
                ])->default('general');
                $table->integer('total_beds')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('ward_id');
                $table->index(['clinic_id', 'is_active']);
            });
            Log::info('create_hospital_structure_tables: rooms created');
        }

        // ── Beds ──────────────────────────────────────────────────────────────
        if (!Schema::hasTable('beds')) {
            Schema::create('beds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
                $table->string('bed_number');
                $table->enum('bed_type', [
                    'general', 'icu', 'nicu', 'maternity', 'pediatric',
                ])->default('general');
                $table->enum('status', [
                    'available', 'occupied', 'cleaning', 'maintenance', 'reserved',
                ])->default('available');
                $table->string('floor')->nullable();
                $table->json('features')->nullable(); // e.g. ["oxygen","ventilator","monitor"]
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('ward_id');
                $table->index(['clinic_id', 'status']);
            });
            Log::info('create_hospital_structure_tables: beds created');
        }

        // ── Hospital Settings ─────────────────────────────────────────────────
        if (!Schema::hasTable('hospital_settings')) {
            Schema::create('hospital_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->unique('clinic_id');
                $table->enum('hospital_type', [
                    'clinic', 'nursing_home', 'hospital', 'multi_specialty',
                ])->default('clinic');
                $table->integer('total_beds')->default(0);
                $table->string('registration_number')->nullable();
                $table->boolean('nabh_accredited')->default(false);
                $table->string('rohini_id')->nullable();
                $table->boolean('emergency_active')->default(false);
                $table->boolean('icu_active')->default(false);
                $table->boolean('pharmacy_active')->default(false);
                $table->boolean('lab_active')->default(false);
                $table->boolean('ipd_active')->default(false);
                $table->boolean('opd_active')->default(false);
                $table->boolean('blood_bank_active')->default(false);
                $table->timestamps();
            });
            Log::info('create_hospital_structure_tables: hospital_settings created');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_settings');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('wards');
    }
};
