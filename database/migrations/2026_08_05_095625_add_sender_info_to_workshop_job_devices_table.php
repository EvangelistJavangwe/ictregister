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
        Schema::table('workshop_job_devices', function (Blueprint $table) {
            // Left null by default, meaning "same as the job's own Sender Information"
            // (device 1's sender info always lives on the job itself — these columns only
            // come into play for an additional device that arrived from a different depot).
            $table->string('depot_name')->nullable()->after('physical_condition_on_receipt');
            $table->string('final_depot')->nullable()->after('depot_name');
            $table->string('department')->nullable()->after('final_depot');
            $table->string('contact_person')->nullable()->after('department');
            $table->string('phone_number')->nullable()->after('contact_person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_job_devices', function (Blueprint $table) {
            $table->dropColumn(['depot_name', 'final_depot', 'department', 'contact_person', 'phone_number']);
        });
    }
};
