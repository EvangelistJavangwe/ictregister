<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE equipment_history MODIFY COLUMN identifier_type ENUM('serial_number','asset_tag','cross_ref_no') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE equipment_history MODIFY COLUMN identifier_type ENUM('serial_number','asset_tag') NOT NULL");
    }
};
