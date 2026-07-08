<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_equipment_registers', function (Blueprint $table) {
            $table->id();
            $table->string('entry_job_number')->unique();
            $table->string('cross_ref_form208')->nullable();
            $table->dateTime('date_time_received');
            $table->string('depot_name')->nullable();
            $table->string('department')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('equipment_type');
            $table->string('brand_make_model')->nullable();
            $table->string('serial_number_asset_tag')->nullable();
            $table->text('accessories_received')->nullable();
            $table->text('nature_of_fault');
            $table->string('physical_condition_on_receipt')->nullable();
            $table->unsignedBigInteger('technician_assigned')->nullable();
            $table->text('repair_action_taken')->nullable();
            $table->date('date_repair_completed')->nullable();
            $table->string('cross_ref_form208_outgoing')->nullable();
            $table->date('date_collected')->nullable();
            $table->string('collector_name')->nullable();
            $table->text('collector_signature')->nullable();
            $table->text('remarks_comments')->nullable();
            $table->enum('priority_level', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->integer('time_taken_value')->nullable();
            $table->enum('time_taken_unit', ['Minutes', 'Hours', 'Days', 'Weeks', 'Months'])->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Collected'])->default('Pending');
            $table->dateTime('due_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('technician_assigned')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_equipment_registers');
    }
};
