<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Custom EMR Templates Table
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Creating custom_emr_templates table');

        if (!Schema::hasTable('custom_emr_templates')) {
            Schema::create('custom_emr_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('specialty', 50)->nullable();
                $table->text('description')->nullable();
                $table->json('fields');
                $table->json('sections')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('version')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->index(['clinic_id', 'specialty']);
                $table->index(['clinic_id', 'is_active']);
            });
        }

        Log::info('custom_emr_templates table created');
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_emr_templates');
    }
};
