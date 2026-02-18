<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guest extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'first_name', 'last_name', 'email'];

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class);
    }
}
