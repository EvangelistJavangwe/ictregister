<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_receiving_registers', function (Blueprint $table) {
            $table->id();
            $table->string('cross_ref_no')->unique();
            $table->date('date_received');
            $table->string('supplier');
            $table->string('delivery_note_no')->nullable();
            $table->string('po_no')->nullable();
            $table->string('item_description');
            $table->string('brand_model')->nullable();
            $table->string('serial_number')->nullable();
            $table->integer('qty_received')->default(1);
            $table->string('physical_condition')->nullable();
            $table->string('functional_test_result')->nullable();
            $table->text('accessories_included')->nullable();
            $table->enum('inspection_status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->date('inspection_date')->nullable();
            $table->string('asset_tag_no')->nullable();
            $table->string('assigned_to_user')->nullable();
            $table->string('depot_department')->nullable();
            $table->date('transfer_date')->nullable();
            $table->string('cross_ref_form208')->nullable();
            $table->string('received_by_user')->nullable();
            $table->text('user_signature')->nullable();
            $table->string('status')->default('Received');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('inspected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_receiving_registers');
    }
};
