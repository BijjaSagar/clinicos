<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_handover_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->unsignedBigInteger('handed_by');   // outgoing staff
            $table->string('shift', 20);               // morning, afternoon, night
            $table->date('handover_date');
            $table->text('general_notes');
            $table->json('patient_notes')->nullable();  // [{admission_id, note}]
            $table->timestamps();

            $table->index(['clinic_id', 'handover_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_handover_notes');
    }
};
