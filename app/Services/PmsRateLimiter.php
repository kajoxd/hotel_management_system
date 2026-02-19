<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class PmsRateLimiter
{
    private const KEY = 'pms_api_request';
    private int $maxRequestsPerSecond;
    private const DECAY_SECONDS = 1;

    public function __construct()
    {
        $this->maxRequestsPerSecond = config('services.pms.api_request_limit');
    }

    /**
     * Block until a request slot is available, then register the request.
     */
    public function throttle(): void
    {
        while (RateLimiter::tooManyAttempts(self::KEY, $this->maxRequestsPerSecond)) {
            usleep(100_000); // 100ms sleep before retrying
        }

        RateLimiter::hit(self::KEY, self::DECAY_SECONDS);
    }

    /**
     * Check if the limit has been reached without blocking.
     */
    public function isLimitReached(): bool
    {
        return RateLimiter::tooManyAttempts(self::KEY, $this->maxRequestsPerSecond);
    }
}
