<?php

namespace App\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class RoomDTO extends DataTransferObject
{
    public int $id;
    public string $number;
    public int $floor;
}
