<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Fix ipd_admissions - add missing columns
        if (Schema::hasTable('ipd_admissions')) {
            Schema::table('ipd_admissions', function (Blueprint $table) {
                if (!Schema::hasColumn('ipd_admissions', 'discharge_date'))
                    $table->timestamp('discharge_date')->nullable()->after('admission_date');
                if (!Schema::hasColumn('ipd_admissions', 'admission_date'))
                    $table->timestamp('admission_date')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'status'))
                    $table->string('status')->default('admitted')->after('discharge_date');
                if (!Schema::hasColumn('ipd_admissions', 'bed_id'))
                    $table->unsignedBigInteger('bed_id')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'ward_id'))
                    $table->unsignedBigInteger('ward_id')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'primary_doctor_id'))
                    $table->unsignedBigInteger('primary_doctor_id')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'admission_number'))
                    $table->string('admission_number')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'diagnosis_at_admission'))
                    $table->text('diagnosis_at_admission')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'discharge_type'))
                    $table->string('discharge_type')->nullable();
                if (!Schema::hasColumn('ipd_admissions', 'final_diagnosis'))
                    $table->text('final_diagnosis')->nullable();
            });
        }

        // Fix pharmacy_items - add missing columns
        if (Schema::hasTable('pharmacy_items')) {
            Schema::table('pharmacy_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pharmacy_items', 'reorder_level'))
                    $table->integer('reorder_level')->default(10)->after('is_active');
                if (!Schema::hasColumn('pharmacy_items', 'is_active'))
                    $table->boolean('is_active')->default(true);
                if (!Schema::hasColumn('pharmacy_items', 'unit'))
                    $table->string('unit')->default('tablet')->nullable();
                if (!Schema::hasColumn('pharmacy_items', 'category'))
                    $table->string('category')->nullable();
                if (!Schema::hasColumn('pharmacy_items', 'hsn_code'))
                    $table->string('hsn_code')->nullable();
                if (!Schema::hasColumn('pharmacy_items', 'gst_rate'))
                    $table->decimal('gst_rate', 5, 2)->default(0);
                if (!Schema::hasColumn('pharmacy_items', 'mrp'))
                    $table->decimal('mrp', 10, 2)->nullable();
                if (!Schema::hasColumn('pharmacy_items', 'description'))
                    $table->text('description')->nullable();
            });
        }

        // Fix hospital_settings - recreate as key-value store if it has wrong structure
        if (Schema::hasTable('hospital_settings') && !Schema::hasColumn('hospital_settings', 'key')) {
            // Drop and recreate as key-value store
            Schema::dropIfExists('hospital_settings');
            Schema::create('hospital_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('clinic_id');
                $table->string('key', 100);
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['clinic_id', 'key']);
                $table->index('clinic_id');
            });
        } elseif (!Schema::hasTable('hospital_settings')) {
            Schema::create('hospital_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('clinic_id');
                $table->string('key', 100);
                $table->text('value')->nullable();
                $table->timestamps();
                $table->unique(['clinic_id', 'key']);
                $table->index('clinic_id');
            });
        }

        // Fix pharmacy_stock - add missing columns
        if (Schema::hasTable('pharmacy_stock')) {
            Schema::table('pharmacy_stock', function (Blueprint $table) {
                if (!Schema::hasColumn('pharmacy_stock', 'quantity_available'))
                    $table->integer('quantity_available')->default(0);
                if (!Schema::hasColumn('pharmacy_stock', 'item_id'))
                    $table->unsignedBigInteger('item_id')->nullable();
                if (!Schema::hasColumn('pharmacy_stock', 'batch_number'))
                    $table->string('batch_number')->nullable();
                if (!Schema::hasColumn('pharmacy_stock', 'expiry_date'))
                    $table->date('expiry_date')->nullable();
                if (!Schema::hasColumn('pharmacy_stock', 'purchase_price'))
                    $table->decimal('purchase_price', 10, 2)->default(0);
                if (!Schema::hasColumn('pharmacy_stock', 'selling_price'))
                    $table->decimal('selling_price', 10, 2)->default(0);
            });
        }
    }

    public function down(): void {}
};
