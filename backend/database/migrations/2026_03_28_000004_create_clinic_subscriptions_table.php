<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('clinic_id')->index();
            $table->enum('plan', ['solo', 'small', 'group', 'enterprise']);
            $table->enum('status', ['trial', 'active', 'paused', 'cancelled', 'expired'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual'])->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('razorpay_subscription_id', 255)->nullable()->unique();
            $table->string('razorpay_plan_id', 255)->nullable();
            $table->string('razorpay_customer_id', 255)->nullable();
            $table->date('next_billing_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_subscriptions');
    }
};
