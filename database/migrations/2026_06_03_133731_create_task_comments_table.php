<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_equipment_id')->constrained('workshop_equipment_registers')->cascadeOnDelete();
            $table->foreignId('commented_by')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_overdue_comment')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
