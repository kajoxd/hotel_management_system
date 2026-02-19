<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

final class BookingSyncCache
{
    /**
     * Cache keys for tracking bookings sync.
     */
    public const SUCCESS = 'bookings_sync_success';
    public const FAILED  = 'bookings_sync_failed';

    /**
     * Initialize the counters to zero.
     */
    public static function initialize(): void
    {
        Cache::put(self::SUCCESS, 0);
        Cache::put(self::FAILED, 0);
    }

    /**
     * Increment the success counter by 1.
     */
    public static function incrementSuccess(): void
    {
        Cache::increment(self::SUCCESS);
    }

    /**
     * Increment the failed counter by 1.
     */
    public static function incrementFailed(): void
    {
        Cache::increment(self::FAILED);
    }

    /**
     * Get the current success count.
     */
    public static function getSuccess(): int
    {
        return Cache::get(self::SUCCESS, 0);
    }

    /**
     * Get the current failed count.
     */
    public static function getFailed(): int
    {
        return Cache::get(self::FAILED, 0);
    }

    /**
     * Reset both counters to zero.
     */
    public static function reset(): void
    {
        self::initialize();
    }
}
