<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->json('serial_numbers')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_receiving_registers', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });
    }
};
