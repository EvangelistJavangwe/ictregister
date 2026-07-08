<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_history', function (Blueprint $table) {
            $table->id();
            $table->enum('identifier_type', ['serial_number', 'asset_tag']);
            $table->string('identifier_value');
            $table->string('event_type');
            $table->text('event_description');
            $table->string('related_table')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('performed_by_name')->nullable();
            $table->timestamp('event_date')->useCurrent();
            $table->index(['identifier_type', 'identifier_value']);

            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_history');
    }
};
