<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make visit_lesions.x_pct and y_pct nullable so a user can add
 * a lesion note without clicking the body-map diagram.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_lesions', function (Blueprint $table) {
            $table->decimal('x_pct', 5, 2)->nullable()->default(50)->change();
            $table->decimal('y_pct', 5, 2)->nullable()->default(50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('visit_lesions', function (Blueprint $table) {
            $table->decimal('x_pct', 5, 2)->nullable(false)->change();
            $table->decimal('y_pct', 5, 2)->nullable(false)->change();
        });
    }
};
