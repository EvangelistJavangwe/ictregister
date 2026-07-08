<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_ref_no', 'asset_description', 'asset_tag_serial_no',
        'model_brand', 'department_user', 'date_acquired', 'condition_at_disposal',
        'reason_for_disposal', 'disposal_method', 'date_disposed', 'approved_by',
        'disposal_value', 'data_wiped_destroyed', 'remarks', 'status',
        'requested_by', 'review_note', 'approved_signature',
    ];

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'date_disposed' => 'date',
            'data_wiped_destroyed' => 'boolean',
            'disposal_value' => 'decimal:2',
        ];
    }

    public static function generateDisposalRefNo(): string
    {
        $year = now()->year;
        $last = self::whereYear('created_at', $year)
            ->whereNotNull('disposal_ref_no')
            ->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->disposal_ref_no, -3)) + 1 : 1;
        return 'ICTD-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
