<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_redistributions', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable()->after('qty_redistributed');
            $table->json('asset_tags')->nullable()->after('serial_numbers');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_redistributions', function (Blueprint $table) {
            $table->dropColumn(['serial_numbers', 'asset_tags']);
        });
    }
};
