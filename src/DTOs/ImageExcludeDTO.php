<?php

namespace MrRobertAmoah\DTO\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use MrRobertAmoah\DTO\BaseDTO;

class ImageExcludeDTO extends BaseDTO
{
    public string $name;
    public string $path;
    public string|int $size;
    public string $mime;
    public string $file;

    protected array $dtoExclude = [
        'size', 'path'
    ];
}