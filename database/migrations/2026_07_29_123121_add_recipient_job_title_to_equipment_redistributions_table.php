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
        Schema::table('equipment_redistributions', function (Blueprint $table) {
            $table->string('recipient_job_title')->nullable()->after('recipient_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_redistributions', function (Blueprint $table) {
            $table->dropColumn('recipient_job_title');
        });
    }
};
