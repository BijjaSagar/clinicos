<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Clinic Locations and Rooms Tables
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Creating clinic_locations table');

        if (!Schema::hasTable('clinic_locations')) {
            Schema::create('clinic_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name', 200);
                $table->text('address');
                $table->string('phone', 15)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('pincode', 6)->nullable();
                $table->json('operating_hours')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->index(['clinic_id', 'is_active']);
            });
        }

        Log::info('Creating clinic_rooms table');

        if (!Schema::hasTable('clinic_rooms')) {
            Schema::create('clinic_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained('clinic_locations')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('room_type', 50)->nullable();
                $table->unsignedSmallInteger('capacity')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['clinic_id', 'location_id']);
            });
        }

        Log::info('Clinic locations and rooms tables created');
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_rooms');
        Schema::dropIfExists('clinic_locations');
    }
};
