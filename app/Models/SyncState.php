<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    protected $fillable = [
        'entity_type',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public static function getCursor(string $entity): ?CarbonInterface
    {
        return self::where('entity_type', $entity)->value('last_synced_at');
    }

    public static function setCursor(string $entity, CarbonInterface $timestamp): void
    {
        self::updateOrCreate(
            ['entity_type' => $entity],
            ['last_synced_at' => $timestamp]
        );
    }
}
