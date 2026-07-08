<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentRedistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'redistribution_ref_no', 'equipment_receiving_register_id', 'redistribution_date',
        'redistribution_type', 'recipient_name', 'depot_department', 'qty_redistributed',
        'serial_numbers', 'asset_tags',
        'cross_ref_form208', 'issued_by', 'receiver_signature', 'issuer_signature',
        'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'redistribution_date' => 'date',
            'qty_redistributed'   => 'integer',
            'serial_numbers'      => 'array',
            'asset_tags'          => 'array',
        ];
    }

    public static function generateRefNo(): string
    {
        $year = now()->year;
        $last = self::whereYear('created_at', $year)->orderByDesc('id')->first();
        $seq  = $last ? ((int) substr($last->redistribution_ref_no, -3)) + 1 : 1;
        return 'RED-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function receivingRegister()
    {
        return $this->belongsTo(EquipmentReceivingRegister::class, 'equipment_receiving_register_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
