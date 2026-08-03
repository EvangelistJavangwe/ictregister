<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workshop_job_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_equipment_register_id')
                ->constrained('workshop_equipment_registers')->cascadeOnDelete();
            $table->string('equipment_type');
            $table->string('brand_make_model')->nullable();
            $table->string('serial_number_asset_tag')->nullable();
            $table->string('physical_condition_on_receipt')->nullable();
            $table->timestamps();

            $table->index('serial_number_asset_tag');
        });

        // Backfill: every existing job's own equipment fields become its "Device 1"
        // row, so nothing that already relied on those columns changes behaviour —
        // this table is additive, existing data is only copied, never moved.
        DB::statement("
            INSERT INTO workshop_job_devices
                (workshop_equipment_register_id, equipment_type, brand_make_model, serial_number_asset_tag, physical_condition_on_receipt, created_at, updated_at)
            SELECT id, equipment_type, brand_make_model, serial_number_asset_tag, physical_condition_on_receipt, created_at, updated_at
            FROM workshop_equipment_registers
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_job_devices');
    }
};
