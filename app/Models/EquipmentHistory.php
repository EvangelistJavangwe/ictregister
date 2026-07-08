<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentHistory extends Model
{
    protected $table = 'equipment_history';

    public $timestamps = false;

    protected $fillable = [
        'identifier_type', 'identifier_value', 'event_type',
        'event_description', 'related_table', 'related_id',
        'performed_by', 'performed_by_name', 'event_date',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
        ];
    }

    public static function record(
        string $identifierType,
        string $identifierValue,
        string $eventType,
        string $description,
        ?string $relatedTable = null,
        ?int $relatedId = null
    ): void {
        $user = auth()->user();
        self::create([
            'identifier_type'  => $identifierType,
            'identifier_value' => $identifierValue,
            'event_type'       => $eventType,
            'event_description'=> $description,
            'related_table'    => $relatedTable,
            'related_id'       => $relatedId,
            'performed_by'     => $user?->id,
            'performed_by_name'=> $user?->full_name ?? 'System',
        ]);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
