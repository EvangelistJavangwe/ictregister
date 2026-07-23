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
        Schema::table('workshop_equipment_registers', function (Blueprint $table) {
            // Destination depot, when a device came from one depot but is heading
            // to a different one after repair — distinct from depot_name (origin).
            $table->string('final_depot')->nullable()->after('depot_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_equipment_registers', function (Blueprint $table) {
            $table->dropColumn('final_depot');
        });
    }
};
