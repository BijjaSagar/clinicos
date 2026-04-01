<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('Creating whatsapp_templates table');
        
        if (!Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', ['appointment_reminder', 'prescription', 'follow_up', 'birthday', 'custom'])->default('custom');
                $table->text('content');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['clinic_id', 'type']);
            });
        }

        Log::info('Creating whatsapp_reminders table');
        
        if (!Schema::hasTable('whatsapp_reminders')) {
            Schema::create('whatsapp_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['appointment_before_1d', 'appointment_before_1h', 'follow_up', 'birthday'])->default('appointment_before_1d');
                $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique(['clinic_id', 'type']);
            });
        }

        Log::info('Creating whatsapp_scheduled table');
        
        if (!Schema::hasTable('whatsapp_scheduled')) {
            Schema::create('whatsapp_scheduled', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
                $table->enum('type', ['appointment_reminder', 'prescription', 'follow_up', 'birthday', 'custom'])->default('custom');
                $table->text('content');
                $table->timestamp('scheduled_for');
                $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->index(['clinic_id', 'status', 'scheduled_for']);
            });
        }

        Log::info('Migration completed: whatsapp_templates, whatsapp_reminders, whatsapp_scheduled tables');
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_scheduled');
        Schema::dropIfExists('whatsapp_reminders');
        Schema::dropIfExists('whatsapp_templates');
    }
};
