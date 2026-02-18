<?php

namespace App\Services;

use App\DTOs\GuestDTO;
use App\DTOs\RoomDTO;
use App\DTOs\RoomTypeDTO;
use Exception;
use Illuminate\Support\Facades\Http;
use App\DTOs\BookingDto;

class PmsApiClient
{
    protected string $pmsBaseUrl;

    public function __construct()
    {
        $this->pmsBaseUrl = config('services.pms.api_base_url');
    }

    /**
     * @throws Exception
     */
    public function fetchBooking(int $bookingId): BookingDto
    {
        $apiUrl = $this->pmsBaseUrl . "/api/bookings/" . $bookingId;

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch booking details for ID {$bookingId}. Response status:" . $response->status());
        }

        return new BookingDTO($response->json());
    }

    /**
     * @throws Exception
     */
    public function fetchRoom(int $roomId): RoomDTO
    {
        $apiUrl = $this->pmsBaseUrl."/api/rooms/" . $roomId;

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch room details for ID {$roomId}. Response status:" . $response->status());
        }

        return new RoomDTO($response->json());
    }

    /**
     * @throws Exception
     */
    public function fetchRoomType(int $roomTypeId): RoomTypeDTO
    {
        $apiUrl = $this->pmsBaseUrl."/api/room-types/" . $roomTypeId;

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch room type details for ID {$roomTypeId}. Response status:" . $response->status());
        }

        return new RoomTypeDTO($response->json());
    }

    /**
     * @throws Exception
     */
    public function fetchGuest(int $guestId): GuestDTO
    {
        $apiUrl = $this->pmsBaseUrl."/api/guests/" . $guestId;

        $response = Http::get($apiUrl);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch guest details for ID {$guestId}. Response status:" . $response->status());
        }

        return new GuestDTO($response->json());
    }
}
