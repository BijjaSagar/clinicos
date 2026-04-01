<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('create_drug_interactions_table: up');

        if (Schema::hasTable('drug_interactions')) {
            Log::warning('create_drug_interactions_table: table already exists, skip');

            return;
        }

        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('drug_a_id')->nullable()->index();
            $table->unsignedBigInteger('drug_b_id')->nullable()->index();
            $table->string('drug_a_name', 255);
            $table->string('drug_b_name', 255);
            $table->enum('severity', ['major', 'moderate', 'minor']);
            $table->text('description');
            $table->text('management')->nullable();
            $table->timestamps();
        });

        Log::info('create_drug_interactions_table: up complete');
    }

    public function down(): void
    {
        Log::info('create_drug_interactions_table: down');

        Schema::dropIfExists('drug_interactions');
    }
};
