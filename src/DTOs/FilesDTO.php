<?php

namespace MrRobertAmoah\DTO\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use MrRobertAmoah\DTO\BaseDTO;

class FilesDTO extends BaseDTO
{
    public ?UploadedFile $one;
    public array $multiple;
    public string $name;

    protected array $dtoFileKeys = [
        'one', 'multiple'
    ];
}