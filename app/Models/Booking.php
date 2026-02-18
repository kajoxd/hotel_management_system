<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id', 'external_id', 'arrival_date', 'departure_date',
        'room_id', 'room_type_id', 'status', 'notes'
    ];

    protected $casts = [
        'arrival_date' => 'date',
        'departure_date' => 'date',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(Guest::class);
    }
}
