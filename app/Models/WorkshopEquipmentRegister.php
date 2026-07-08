<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkshopEquipmentRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_job_number', 'cross_ref_form208', 'date_time_received',
        'depot_name', 'department', 'contact_person', 'phone_number',
        'equipment_type', 'brand_make_model', 'serial_number_asset_tag',
        'accessories_received', 'nature_of_fault', 'physical_condition_on_receipt',
        'technician_assigned', 'repair_action_taken', 'date_repair_completed',
        'cross_ref_form208_outgoing', 'date_collected', 'collector_name',
        'collector_signature', 'remarks_comments', 'priority_level',
        'time_taken_value', 'time_taken_unit', 'status', 'due_date',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_time_received' => 'datetime',
            'date_repair_completed' => 'date',
            'date_collected' => 'date',
            'due_date' => 'datetime',
            'time_taken_value' => 'integer',
        ];
    }

    public static function generateJobNumber(): string
    {
        $year = now()->year;
        $last = self::whereYear('created_at', $year)
            ->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->entry_job_number, -3)) + 1 : 1;
        return 'JB-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && now()->greaterThan($this->due_date)
            && !in_array($this->status, ['Completed', 'Collected']);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_assigned');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'workshop_equipment_id')->latest();
    }
}
