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
        Schema::table('workshop_job_devices', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Collected'])
                ->default('Pending')->after('physical_condition_on_receipt');
            $table->text('repair_action_taken')->nullable()->after('status');
            $table->date('date_repair_completed')->nullable()->after('repair_action_taken');
            $table->date('date_collected')->nullable()->after('date_repair_completed');
            $table->string('collector_name')->nullable()->after('date_collected');
            $table->text('collector_signature')->nullable()->after('collector_name');
        });

        // Backfill: every existing device row is a job's original "device 1" (1:1 from the
        // prior migration's backfill), so it inherits that job's current status/repair/
        // collection details directly — no data is invented, only copied across.
        DB::statement("
            UPDATE workshop_job_devices d
            INNER JOIN workshop_equipment_registers j ON j.id = d.workshop_equipment_register_id
            SET d.status = j.status,
                d.repair_action_taken = j.repair_action_taken,
                d.date_repair_completed = j.date_repair_completed,
                d.date_collected = j.date_collected,
                d.collector_name = j.collector_name,
                d.collector_signature = j.collector_signature
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_job_devices', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'repair_action_taken', 'date_repair_completed',
                'date_collected', 'collector_name', 'collector_signature',
            ]);
        });
    }
};
