<?php

namespace App\Repositories;

use App\DTOs\GuestDTO;
use App\Models\Guest;

class GuestRepository
{
    public function saveFromDto(GuestDTO $dto): Guest
    {
        return Guest::updateOrCreate(
            ['id' => $dto->id],
            [
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'email' => $dto->email,
            ]
        );
    }
}
