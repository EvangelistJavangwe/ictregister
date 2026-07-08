<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $fillable = [
        'workshop_equipment_id', 'commented_by', 'comment', 'is_overdue_comment',
    ];

    protected function casts(): array
    {
        return [
            'is_overdue_comment' => 'boolean',
        ];
    }

    public function workshopEquipment()
    {
        return $this->belongsTo(WorkshopEquipmentRegister::class, 'workshop_equipment_id');
    }

    public function commenter()
    {
        return $this->belongsTo(User::class, 'commented_by');
    }
}
