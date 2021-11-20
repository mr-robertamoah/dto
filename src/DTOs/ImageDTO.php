<?php

namespace MrRobertAmoah\DTO\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use MrRobertAmoah\DTO\BaseDTO;

class ImageDTO extends BaseDTO
{
    public string $name;
    public string $path;
    public string|int $id;
    public string $mime;
    public ?string $file;
}