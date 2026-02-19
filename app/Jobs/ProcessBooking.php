<?php

namespace App\Jobs;

use App\DTOs\BookingDTO;
use App\DTOs\GuestDto;
use App\DTOs\RoomDto;
use App\DTOs\RoomTypeDto;
use App\Helpers\BookingSyncCache;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\PmsApiClient;
use App\Services\PmsRateLimiter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;
    private PmsApiClient $pmsClient;
    private PmsRateLimiter $rateLimiter;

    public function __construct(
        private readonly int $bookingId,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function handle(
        PmsApiClient       $pmsClient,
        PmsRateLimiter     $rateLimiter,
        RoomTypeRepository $roomTypeRepository,
        RoomRepository     $roomRepository,
        GuestRepository    $guestRepository,
        BookingRepository  $bookingRepository,
    ): void
    {
        $this->pmsClient = $pmsClient;
        $this->rateLimiter = $rateLimiter;

        try {
            $bookingDto = $this->fetchBooking($this->bookingId);

            $roomTypeDto = $this->fetchRoomType($bookingDto->room_type_id);

            $roomDto = $this->fetchRoom($bookingDto->room_id);

            $guestDtos = $this->getAllGuests($bookingDto->guest_ids);

            DB::transaction(function () use (
                $bookingRepository,
                $guestRepository,
                $roomRepository,
                $roomTypeRepository,
                $bookingDto,
                $roomTypeDto,
                $roomDto,
                $guestDtos
            ) {
                if ($roomTypeDto) {
                    $roomTypeRepository->saveFromDto($roomTypeDto);
                }

                if ($roomDto) {
                    $roomRepository->saveFromDto($roomDto);
                }

                foreach ($guestDtos as $guestDto) {
                    $guestRepository->saveFromDto($guestDto);
                }

                $booking = $bookingRepository->saveFromDto($bookingDto);

                $booking->guests()->sync($bookingDto->guest_ids);
            });
        } catch (Exception $e) {
            Log::error('Error processing booking', [
                'booking_id' => $this->bookingId,
                'message'    => $e->getMessage(),
            ]);

            throw $e;
        }

        BookingSyncCache::incrementSuccess();
    }

    /**
     * @throws Exception
     */
    private function fetchBooking(int $bookingId): BookingDTO
    {
        $this->rateLimiter->throttle();

        return $this->pmsClient->fetchBooking($bookingId);
    }

    /**
     * @throws Exception
     */
    private function fetchRoom(int $roomId): ?RoomDto
    {
        if (Room::find($roomId)) {
            return null;
        }

        $this->rateLimiter->throttle();

        return $this->pmsClient->fetchRoom($roomId);
    }

    /**
     * @throws Exception
     */
    private function fetchRoomType(int $roomTypeId): ?RoomTypeDto
    {
        if (RoomType::find($roomTypeId)) {
            return null;
        }

        $this->rateLimiter->throttle();

        return $this->pmsClient->fetchRoomType($roomTypeId);
    }

    /**
     * @throws Exception
     */
    private function fetchGuest(int $guestId): ?GuestDto
    {
        if (Guest::find($guestId)) {
            return null;
        }

        $this->rateLimiter->throttle();

        return $this->pmsClient->fetchGuest($guestId);
    }

    /**
     * @throws Exception
     */
    private function getAllGuests(array $guests): array
    {
        $guestDtos = [];

        foreach ($guests as $guestId) {
            $dto = $this->fetchGuest($guestId);

            if ($dto) {
                $guestDtos[] = $dto;
            }
        }

        return $guestDtos;
    }

    public function failed(Exception $exception): void
    {
        Log::critical('Booking job permanently failed', [
            'booking_id' => $this->bookingId,
            'message' => $exception->getMessage()
        ]);

        BookingSyncCache::incrementFailed();
    }
}
