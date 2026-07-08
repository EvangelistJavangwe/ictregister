<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('disposal_ref_no')->unique()->nullable();
            $table->string('asset_description');
            $table->string('asset_tag_serial_no')->nullable();
            $table->string('model_brand')->nullable();
            $table->string('department_user')->nullable();
            $table->date('date_acquired')->nullable();
            $table->string('condition_at_disposal')->nullable();
            $table->text('reason_for_disposal');
            $table->string('disposal_method')->nullable();
            $table->date('date_disposed')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->decimal('disposal_value', 10, 2)->nullable();
            $table->boolean('data_wiped_destroyed')->default(false);
            $table->text('remarks')->nullable();
            $table->enum('status', ['Requested', 'Pending Approval', 'Approved', 'Rejected', 'Disposed'])->default('Requested');
            $table->unsignedBigInteger('requested_by');
            $table->text('review_note')->nullable();
            $table->text('approved_signature')->nullable();
            $table->timestamps();

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('requested_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_disposals');
    }
};
