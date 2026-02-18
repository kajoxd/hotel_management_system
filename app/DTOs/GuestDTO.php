<?php

namespace App\DTOs;

use Spatie\DataTransferObject\DataTransferObject;

class GuestDTO extends DataTransferObject
{
    public int $id;
    public string $first_name;
    public string $last_name;
    public string $email;
}
