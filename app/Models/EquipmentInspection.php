<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_ref_no', 'equipment_receiving_register_id', 'qty_inspected',
        'inspection_status', 'physical_condition', 'functional_test_result',
        'accessories_checked', 'inspected_by', 'inspection_date', 'asset_tag_no',
        'assigned_to_user', 'depot_department', 'transfer_date', 'cross_ref_form208',
        'received_by_user', 'receiver_signature', 'inspector_signature',
        'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'transfer_date'   => 'date',
            'qty_inspected'   => 'integer',
        ];
    }

    public static function generateRefNo(): string
    {
        $year = now()->year;
        $last = self::whereYear('created_at', $year)->orderByDesc('id')->first();
        $seq  = $last ? ((int) substr($last->inspection_ref_no, -3)) + 1 : 1;
        return 'INS-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function receivingRegister()
    {
        return $this->belongsTo(EquipmentReceivingRegister::class, 'equipment_receiving_register_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
