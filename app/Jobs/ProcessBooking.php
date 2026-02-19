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
use App\Services\PmsRateLimiter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        private readonly int            $bookingId,
        private readonly PmsApiClient   $pmsClient,
        private readonly PmsRateLimiter $rateLimiter,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $this->rateLimiter->throttle();

                $bookingDto = $this->pmsClient->fetchBooking($this->bookingId);

                $this->persistRoomType($bookingDto->room_type_id);
                $this->persistRoom($bookingDto->room_id);

                foreach ($bookingDto->guest_ids as $guestId) {
                    $this->persistGuest($guestId);
                }

                $booking = $this->persistBooking($bookingDto);

                $booking->guests()->sync($bookingDto->guest_ids);
            });
        } catch (Exception $e) {
            Log::error('Error processing booking', [
                'booking_id' => $this->bookingId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
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

        $this->rateLimiter->throttle();

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

        $this->rateLimiter->throttle();

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

        $this->rateLimiter->throttle();

        $guestDTO = $this->pmsClient->fetchGuest($guestId);
        $repository = new GuestRepository();

        $repository->saveFromDto($guestDTO);
    }

    public function failed(Exception $exception): void
    {
        Log::critical('Booking job permanently failed', [
            'booking_id' => $this->bookingId,
            'message' => $exception->getMessage()
        ]);
    }
}
