<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopJobDevice extends Model
{
    protected $fillable = [
        'workshop_equipment_register_id', 'equipment_type', 'brand_make_model',
        'serial_number_asset_tag', 'physical_condition_on_receipt',
        'status', 'repair_action_taken', 'date_repair_completed',
        'date_collected', 'collector_name', 'collector_signature',
    ];

    protected function casts(): array
    {
        return [
            'date_repair_completed' => 'date',
            'date_collected' => 'date',
        ];
    }

    public function job()
    {
        return $this->belongsTo(WorkshopEquipmentRegister::class, 'workshop_equipment_register_id');
    }
}
