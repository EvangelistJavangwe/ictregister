<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkshopJobDevice extends Model
{
    protected $fillable = [
        'workshop_equipment_register_id', 'equipment_type', 'brand_make_model',
        'serial_number_asset_tag', 'physical_condition_on_receipt',
        'depot_name', 'final_depot', 'department', 'contact_person', 'phone_number',
        'status', 'repair_action_taken', 'date_repair_completed',
        'date_collected', 'collector_name', 'collector_signature',
    ];

    /** True when this device has its own Sender Information rather than sharing the job's. */
    public function hasOwnSenderInfo(): bool
    {
        return !empty($this->depot_name);
    }

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
