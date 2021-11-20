<?php

namespace MrRobertAmoah\DTO\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use MrRobertAmoah\DTO\BaseDTO;

class ExampleDTO extends BaseDTO
{
    public string $name;
    public string $email;
    public string|int $userId;
    public string $date;

    public function fromRequestExtension(Request $request) : BaseDTO
    {
        $this->date = new Carbon();

        return $this;
    }
}