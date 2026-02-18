<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'description'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
