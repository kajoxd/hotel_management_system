<?php

namespace App\Jobs;

use App\DTOs\BookingDto;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Repositories\BookingRepository;
use App\Repositories\GuestRepository;
use App\Repositories\RoomRepository;
use App\Repositories\RoomTypeRepository;
use App\Services\PmsApiClient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBookingBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $pmsApiRequestLimit;
    private float $lastRequestTime = 0;

    public function __construct(private readonly array        $bookingsBatch,
                                private readonly PmsApiClient $pmsClient,
    )
    {
        $this->pmsApiRequestLimit = config('services.pms.api_request_limit');
    }

    public function handle(): void
    {
        foreach ($this->bookingsBatch as $bookingId) {
            try {
                $this->processBooking($bookingId);
            } catch (Exception $e) {
                Log::error('Error processing booking', [
                    'booking_id' => $bookingId,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @throws Exception
     */
    private function processBooking(int $bookingId): void
    {
        DB::transaction(function () use ($bookingId) {
            $this->rateLimitRequest();
            $bookingDto = $this->pmsClient->fetchBooking($bookingId);

            $this->persistRoomType($bookingDto->room_type_id);
            $this->persistRoom($bookingDto->room_id);

            foreach ($bookingDto->guest_ids as $guestId) {
                $this->persistGuest($guestId);
            }

            $booking = $this->persistBooking($bookingDto);

            $booking->guests()->sync($bookingDto->guest_ids);
        });
    }

    /**
     * @throws Exception
     */
    private function persistBooking(BookingDto $bookingDto): Booking
    {
        $repository = new BookingRepository();

        return $repository->saveFromDto($bookingDto);
    }

    /**
     * @throws Exception
     */
    private function persistRoom(int $roomId): void
    {
        $room = Room::find($roomId);

        if ($room) {
            return;
        }

        $this->rateLimitRequest();

        $roomDTO = $this->pmsClient->fetchRoom($roomId);
        $repository = new RoomRepository();

        $repository->saveFromDto($roomDTO);
    }

    /**
     * @throws Exception
     */
    private function persistRoomType(int $roomTypeId): void
    {
        $roomType = RoomType::find($roomTypeId);

        if ($roomType) {
            return;
        }

        $this->rateLimitRequest();

        $roomTypeDTO = $this->pmsClient->fetchRoomType($roomTypeId);
        $repository = new RoomTypeRepository();

        $repository->saveFromDto($roomTypeDTO);
    }

    /**
     * @throws Exception
     */
    private function persistGuest(int $guestId): void
    {
        $guest = Guest::find($guestId);

        if ($guest) {
            return;
        }

        $this->rateLimitRequest();

        $guestDTO = $this->pmsClient->fetchGuest($guestId);
        $repository = new GuestRepository();

        $repository->saveFromDto($guestDTO);
    }

    private function rateLimitRequest(): void
   {
       $currentTime = microtime(true);
       $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
       $minimumInterval = 1 / $this->pmsApiRequestLimit;

       if ($timeSinceLastRequest < $minimumInterval) {
           $sleepMicroseconds = ($minimumInterval - $timeSinceLastRequest) * 1000000;
           usleep((int) $sleepMicroseconds);
       }

       $this->lastRequestTime = microtime(true);
   }
}
