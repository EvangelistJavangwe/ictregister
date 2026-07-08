<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->date('warranty_start_date')->nullable()->after('brand_model');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date');
            $table->text('warranty_notes')->nullable()->after('warranty_end_date');
            $table->boolean('warranty_expiry_logged')->default(false)->after('warranty_notes');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->dropColumn(['warranty_start_date', 'warranty_end_date', 'warranty_notes', 'warranty_expiry_logged']);
        });
    }
};
