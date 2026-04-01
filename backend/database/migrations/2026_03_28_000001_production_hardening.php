<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('production_hardening migration: up');

        if (!Schema::hasTable('razorpay_webhook_events')) {
            Schema::create('razorpay_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 120)->unique();
                $table->string('event_type', 120)->nullable();
                $table->longText('payload_json')->nullable();
                $table->string('payload_hash', 64)->nullable()->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->string('razorpay_payment_id', 100)->nullable()->index();
                $table->timestamp('processed_at')->nullable();
                $table->text('processing_note')->nullable();
                $table->timestamps();
            });
            Log::info('production_hardening: created razorpay_webhook_events');
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'payment_link')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('payment_link', 500)->nullable()->after('notes');
            });
            Log::info('production_hardening: invoices.payment_link added');
        }

        if (Schema::hasTable('payments')) {
            if (!Schema::hasColumn('payments', 'razorpay_refund_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('razorpay_refund_id', 100)->nullable()->after('razorpay_signature');
                });
            }
            if (!Schema::hasColumn('payments', 'refund_amount')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->decimal('refund_amount', 12, 2)->nullable()->after('razorpay_refund_id');
                });
            }
            Log::info('production_hardening: payments refund columns ensured');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                try {
                    DB::statement(
                        "ALTER TABLE payments MODIFY payment_method ENUM("
                        . "'upi','card','cash','netbanking','wallet','insurance','advance','razorpay'"
                        . ") NOT NULL DEFAULT 'cash'"
                    );
                    Log::info('production_hardening: payments.payment_method extended with razorpay');
                } catch (\Throwable $e) {
                    Log::warning('production_hardening: could not alter payment_method enum', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('production_hardening migration: up complete');
    }

    public function down(): void
    {
        Log::info('production_hardening migration: down');

        Schema::dropIfExists('razorpay_webhook_events');

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'payment_link')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('payment_link');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'refund_amount')) {
                    $table->dropColumn('refund_amount');
                }
                if (Schema::hasColumn('payments', 'razorpay_refund_id')) {
                    $table->dropColumn('razorpay_refund_id');
                }
            });
        }

        Log::info('production_hardening migration: down complete');
    }
};
