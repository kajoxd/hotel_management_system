<?php

namespace App\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class BookingDTO extends DataTransferObject
{
    public int $id;
    public string $external_id;
    public string $arrival_date;
    public string $departure_date;
    public int $room_id;
    public int $room_type_id;
    public string $status;
    public ?string $notes = null;
    public array $guest_ids = [];
}
