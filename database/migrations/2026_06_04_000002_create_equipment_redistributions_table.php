<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_redistributions', function (Blueprint $table) {
            $table->id();
            $table->string('redistribution_ref_no')->unique();
            $table->unsignedBigInteger('equipment_receiving_register_id');
            $table->date('redistribution_date');
            $table->enum('redistribution_type', ['Individual', 'Depot/Department']);
            $table->string('recipient_name');
            $table->string('depot_department')->nullable();
            $table->integer('qty_redistributed')->default(1);
            $table->string('cross_ref_form208')->nullable();
            $table->string('issued_by')->nullable();
            $table->text('receiver_signature')->nullable();
            $table->text('issuer_signature')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('equipment_receiving_register_id', 'fk_redist_receiving_id')
                ->references('id')->on('equipment_receiving_registers')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_redistributions');
    }
};
