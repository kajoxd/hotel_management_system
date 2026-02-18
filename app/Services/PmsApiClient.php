<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\DTOs\BookingDto;

class PmsApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.pms.api_base_url');
    }

    public function getBooking(int $bookingId): BookingDto
    {
        $response = Http::get("{$this->baseUrl}/api/bookings/{$bookingId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch booking {$bookingId}");
        }

        return new BookingDto($response->json());
    }

    public function getRoom(int $roomId): array
    {
        $response = Http::get("{$this->baseUrl}/api/rooms/{$roomId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch room {$roomId}");
        }

        return $response->json();
    }

    public function getRoomType(int $roomTypeId): array
    {
        $response = Http::get("{$this->baseUrl}/api/room-types/{$roomTypeId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch room type {$roomTypeId}");
        }

        return $response->json();
    }

    public function getGuest(int $guestId): array
    {
        $response = Http::get("{$this->baseUrl}/api/guests/{$guestId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch guest {$guestId}");
        }

        return $response->json();
    }
}
