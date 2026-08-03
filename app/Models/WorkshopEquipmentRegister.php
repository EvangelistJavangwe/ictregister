<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkshopEquipmentRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_job_number', 'cross_ref_form208', 'date_time_received',
        'depot_name', 'final_depot', 'department', 'contact_person', 'phone_number',
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

    /** Every device on this job, oldest first — the first row mirrors the job's own equipment columns. */
    public function devices()
    {
        return $this->hasMany(WorkshopJobDevice::class, 'workshop_equipment_register_id')->oldest('id');
    }

    /**
     * The job's own status is an aggregate of its devices' individual statuses, so
     * e.g. a job with one Collected device and two still In Progress reads as
     * "In Progress" overall, while list/dashboard/export views (which only know
     * about the job-level column) keep showing something meaningful.
     */
    public function computeAggregateStatus(): string
    {
        $statuses = $this->devices->pluck('status');
        if ($statuses->isEmpty()) {
            return $this->status;
        }

        if ($statuses->every(fn($s) => $s === 'Collected')) return 'Collected';
        if ($statuses->every(fn($s) => in_array($s, ['Completed', 'Collected']))) return 'Completed';
        if ($statuses->contains(fn($s) => in_array($s, ['In Progress', 'Completed', 'Collected']))) return 'In Progress';
        return 'Pending';
    }
}
