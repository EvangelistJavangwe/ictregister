<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('brand_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
