<?php

namespace App\Repositories;

use App\DTOs\RoomDTO;
use App\Models\Room;

class RoomRepository
{
    public function saveFromDto(RoomDTO $dto): Room
    {
        return Room::updateOrCreate(
            ['id' => $dto->id],
            [
                'number' => $dto->number,
                'floor' => $dto->floor,
            ]
        );
    }
}
