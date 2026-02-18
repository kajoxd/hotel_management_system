<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'number', 'floor'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
