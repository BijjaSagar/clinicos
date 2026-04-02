<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');          // recipient
            $table->unsignedBigInteger('clinic_id');
            $table->string('type', 50);                     // lab_result, checkin, critical_result, etc.
            $table->string('title', 200);
            $table->text('body');
            $table->string('action_url', 500)->nullable();  // link to click
            $table->string('icon', 30)->default('bell');    // bell, flask, alert, etc.
            $table->string('colour', 20)->default('blue'); // blue, red, green, amber
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['clinic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};
