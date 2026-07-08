<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_ref_no')->unique();
            $table->unsignedBigInteger('equipment_receiving_register_id');
            $table->integer('qty_inspected')->default(1);
            $table->enum('inspection_status', ['Accepted', 'Rejected', 'Pending'])->default('Pending');
            $table->string('physical_condition')->nullable();
            $table->string('functional_test_result')->nullable();
            $table->string('accessories_checked')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->date('inspection_date')->nullable();
            $table->string('asset_tag_no')->nullable();
            $table->string('assigned_to_user')->nullable();
            $table->string('depot_department')->nullable();
            $table->date('transfer_date')->nullable();
            $table->string('cross_ref_form208')->nullable();
            $table->string('received_by_user')->nullable();
            $table->text('receiver_signature')->nullable();
            $table->text('inspector_signature')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('equipment_receiving_register_id', 'fk_insp_receiving_id')
                ->references('id')->on('equipment_receiving_registers')->cascadeOnDelete();
            $table->foreign('inspected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'fk_insp_created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
    }
};
