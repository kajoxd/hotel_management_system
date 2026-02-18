<?php

namespace App\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class RoomTypeDTO extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $description;
}
