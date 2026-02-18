<?php

namespace App\Repositories;

use App\DTOs\BookingDto;
use App\Models\Booking;

class BookingRepository
{
    public function saveFromDto(BookingDto $dto): Booking
    {
        return Booking::updateOrCreate(
            ['id' => $dto->id],
            [
                'external_id' => $dto->external_id,
                'arrival_date' => $dto->arrival_date,
                'departure_date' => $dto->departure_date,
                'room_id' => $dto->room_id,
                'room_type_id' => $dto->room_type_id,
                'status' => $dto->status,
                'notes' => $dto->notes,
            ]
        );
    }
}
