<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * HIMS — Pharmacy Tables
 * Creates: pharmacy_categories, pharmacy_items, pharmacy_stock,
 *          pharmacy_suppliers, pharmacy_purchases, pharmacy_purchase_items,
 *          pharmacy_dispensing, pharmacy_dispensing_items
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_pharmacy_tables: up');

        // ── Pharmacy Suppliers ────────────────────────────────────────────────
        // Created before purchases so purchase FK can reference it.
        if (!Schema::hasTable('pharmacy_suppliers')) {
            Schema::create('pharmacy_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('contact_person')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('gst_number')->nullable();
                $table->string('drug_license')->nullable();
                $table->string('payment_terms')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'is_active']);
            });
            Log::info('create_pharmacy_tables: pharmacy_suppliers created');
        }

        // ── Pharmacy Categories ───────────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_categories')) {
            Schema::create('pharmacy_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->foreign('parent_id')->references('id')->on('pharmacy_categories')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'parent_id']);
            });
            Log::info('create_pharmacy_tables: pharmacy_categories created');
        }

        // ── Pharmacy Items ────────────────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_items')) {
            Schema::create('pharmacy_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->foreignId('drug_id')->nullable()->constrained('indian_drugs')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('pharmacy_categories')->nullOnDelete();
                $table->string('hsn_code')->nullable();
                $table->string('unit')->default('Tablets');
                $table->integer('pack_size')->default(1);
                $table->string('manufacturer')->nullable();
                $table->enum('schedule', ['H', 'H1', 'G', 'X', 'OTC'])->nullable();
                $table->boolean('is_controlled')->default(false);
                $table->decimal('gst_rate', 4, 2)->default(12.00);
                $table->decimal('mrp', 10, 2);
                $table->decimal('selling_price', 10, 2);
                $table->integer('reorder_level')->default(10);
                $table->integer('reorder_qty')->default(100);
                $table->string('storage_conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'is_active']);
                $table->index('drug_id');
            });
            Log::info('create_pharmacy_tables: pharmacy_items created');
        }

        // ── Pharmacy Purchases ────────────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_purchases')) {
            Schema::create('pharmacy_purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('pharmacy_suppliers')->nullOnDelete();
                $table->string('purchase_number')->unique();
                $table->string('invoice_number')->nullable();
                $table->date('invoice_date')->nullable();
                $table->foreignId('received_by')->constrained('users');
                $table->date('received_date');
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('gst_amount', 10, 2)->default(0);
                $table->decimal('net_amount', 12, 2)->default(0);
                $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index(['clinic_id', 'payment_status']);
                $table->index('supplier_id');
            });
            Log::info('create_pharmacy_tables: pharmacy_purchases created');
        }

        // ── Pharmacy Stock ────────────────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_stock')) {
            Schema::create('pharmacy_stock', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('pharmacy_items')->cascadeOnDelete();
                $table->string('batch_number');
                $table->date('expiry_date');
                $table->integer('quantity_in')->default(0);
                $table->integer('quantity_out')->default(0);
                $table->integer('quantity_available')->default(0); // maintained via application logic
                $table->decimal('purchase_rate', 10, 2);
                $table->decimal('mrp', 10, 2);
                $table->foreignId('supplier_id')->nullable()->constrained('pharmacy_suppliers')->nullOnDelete();
                $table->foreignId('grn_id')->nullable()->constrained('pharmacy_purchases')->nullOnDelete();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('item_id');
                $table->index(['item_id', 'expiry_date']);
                $table->index(['clinic_id', 'item_id']);
            });
            Log::info('create_pharmacy_tables: pharmacy_stock created');
        }

        // ── Pharmacy Purchase Items ───────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_purchase_items')) {
            Schema::create('pharmacy_purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained('pharmacy_purchases')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('pharmacy_items')->cascadeOnDelete();
                $table->string('batch_number');
                $table->date('expiry_date');
                $table->integer('quantity');
                $table->integer('free_quantity')->default(0);
                $table->decimal('purchase_rate', 10, 2);
                $table->decimal('mrp', 10, 2);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('gst_rate', 4, 2)->default(12);
                $table->decimal('net_amount', 10, 2);
                $table->timestamps();
                $table->index('purchase_id');
                $table->index('item_id');
            });
            Log::info('create_pharmacy_tables: pharmacy_purchase_items created');
        }

        // ── Pharmacy Dispensing ───────────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_dispensing')) {
            Schema::create('pharmacy_dispensing', function (Blueprint $table) {
                $table->id();
                $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('admission_id')->nullable()->constrained('ipd_admissions')->nullOnDelete();
                $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
                $table->foreignId('dispensed_by')->constrained('users');
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('dispensing_number')->unique();
                $table->timestamp('dispensed_at');
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('paid_amount', 10, 2)->default(0);
                $table->enum('payment_mode', ['cash', 'card', 'upi', 'credit'])->default('cash');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index('clinic_id');
                $table->index('patient_id');
                $table->index('admission_id');
                $table->index(['clinic_id', 'dispensed_at']);
            });
            Log::info('create_pharmacy_tables: pharmacy_dispensing created');
        }

        // ── Pharmacy Dispensing Items ─────────────────────────────────────────
        if (!Schema::hasTable('pharmacy_dispensing_items')) {
            Schema::create('pharmacy_dispensing_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispensing_id')->constrained('pharmacy_dispensing')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('pharmacy_items')->cascadeOnDelete();
                $table->string('batch_number')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 10, 2);
                $table->decimal('gst_amount', 10, 2)->default(0);
                $table->decimal('total_price', 10, 2);
                $table->text('instructions')->nullable();
                $table->timestamps();
                $table->index('dispensing_id');
                $table->index('item_id');
            });
            Log::info('create_pharmacy_tables: pharmacy_dispensing_items created');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_dispensing_items');
        Schema::dropIfExists('pharmacy_dispensing');
        Schema::dropIfExists('pharmacy_purchase_items');
        Schema::dropIfExists('pharmacy_stock');
        Schema::dropIfExists('pharmacy_purchases');
        Schema::dropIfExists('pharmacy_items');
        Schema::dropIfExists('pharmacy_categories');
        Schema::dropIfExists('pharmacy_suppliers');
    }
};
