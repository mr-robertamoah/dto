<?php

namespace MrRobertAmoah\DTO\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use MrRobertAmoah\DTO\BaseDTO;

class ExampleDTO extends BaseDTO
{
    public string $name;
    public string $email;
    public string $date;

    public function fromRequestExtension(Request $request)
    {
        return $this->date = new Carbon();
    }
}