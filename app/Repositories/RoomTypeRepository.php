<?php

namespace App\Repositories;

use App\DTOs\RoomTypeDTO;
use App\Models\RoomType;

class RoomTypeRepository
{
    public function saveFromDto(RoomTypeDTO $dto): RoomType
    {
        return RoomType::updateOrCreate(
            ['id' => $dto->id],
            [
                'name' => $dto->name,
                'description' => $dto->description,
            ]
        );
    }
}
