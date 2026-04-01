<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Lab Integration Tables
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Creating lab orders table');

        if (!Schema::hasTable('lab_orders')) {
            Schema::create('lab_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->constrained();
                $table->foreignId('visit_id')->nullable()->constrained();
                $table->string('order_number', 30)->unique();
                $table->string('provider', 50);
                $table->string('provider_name', 100);
                $table->string('external_order_id', 100)->nullable();
                $table->json('tests');
                $table->decimal('total_amount', 10, 2);
                $table->enum('sample_collection_type', ['home', 'lab'])->default('lab');
                $table->date('collection_date')->nullable();
                $table->text('collection_address')->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'sample_collected', 'processing', 'completed', 'cancelled'])->default('pending');
                $table->string('result_url', 500)->nullable();
                $table->dateTime('result_received_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->timestamps();
                $table->index(['clinic_id', 'status']);
                $table->index('patient_id');
                $table->index('order_number');
            });
        }

        Log::info('Lab orders table created');
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
