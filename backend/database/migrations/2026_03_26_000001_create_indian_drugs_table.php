<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('Creating indian_drugs table');
        
        if (!Schema::hasTable('indian_drugs')) {
            Schema::create('indian_drugs', function (Blueprint $table) {
                $table->id();
                $table->string('generic_name');
                $table->json('brand_names')->nullable();
                $table->string('drug_class')->nullable();
                $table->string('form'); // Tablet, Capsule, Cream, Gel, etc.
                $table->string('strength'); // 500mg, 10%, etc.
                $table->string('manufacturer')->nullable();
                $table->string('schedule')->default('H'); // H, H1, X, OTC
                $table->json('common_dosages')->nullable();
                $table->json('contraindications')->nullable();
                $table->json('interactions')->nullable();
                $table->json('side_effects')->nullable();
                $table->boolean('is_controlled')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('generic_name');
                $table->index('drug_class');
                $table->index('form');
                if (config('database.default') !== 'sqlite') {
                    $table->fullText(['generic_name']);
                }
            });
        }

        Log::info('Creating drug_interactions table');
        
        if (!Schema::hasTable('drug_interactions')) {
            Schema::create('drug_interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('drug_a_id')->constrained('indian_drugs')->cascadeOnDelete();
                $table->foreignId('drug_b_id')->constrained('indian_drugs')->cascadeOnDelete();
                $table->enum('severity', ['minor', 'moderate', 'major', 'contraindicated'])->default('moderate');
                $table->text('description')->nullable();
                $table->text('management')->nullable();
                $table->timestamps();
                
                $table->unique(['drug_a_id', 'drug_b_id']);
            });
        }

        Log::info('Creating prescription_items table');
        
        if (!Schema::hasTable('prescription_items')) {
            Schema::create('prescription_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('drug_id')->nullable()->constrained('indian_drugs')->nullOnDelete();
                $table->string('drug_name'); // In case drug_id is null
                $table->string('dosage');
                $table->string('frequency'); // OD, BD, TDS, QID, SOS, etc.
                $table->string('duration'); // 5 days, 1 week, 2 weeks, etc.
                $table->string('route')->default('oral'); // oral, topical, IV, IM, etc.
                $table->text('instructions')->nullable(); // Before food, after food, etc.
                $table->integer('quantity')->nullable();
                $table->boolean('is_substitutable')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Log::info('Creating prescription_templates table');
        
        if (!Schema::hasTable('prescription_templates')) {
            Schema::create('prescription_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('diagnosis')->nullable();
                $table->string('specialty')->nullable();
                $table->json('medications'); // Array of medication objects
                $table->text('instructions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Log::info('Migration completed: indian_drugs, drug_interactions, prescription_items, prescription_templates tables');
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('drug_interactions');
        Schema::dropIfExists('indian_drugs');
    }
};
