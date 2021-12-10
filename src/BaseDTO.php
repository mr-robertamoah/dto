<?php

namespace MrRobertAmoah\DTO;

use MrRobertAmoah\DTO\Traits\DTOTrait;
use Illuminate\Http\Request;

abstract class BaseDTO
{
    use DTOTrait;

    const EXCLUDED = [
        'dtoDataKeys', 
        'dtoFileKeys', 
        'dtoExclude', 
        'dtoOnly', 
        'dtoReflectionObject'
    ];


    /*
    * these are the names of properties to be
    * excluded when creating the dto
    */
    protected array $dtoExclude = [];

    /*
    * these are the names of properties to be
    * set when creating the dto.
    * the excluded will be ignored when
    * these values are set
    */
    protected array $dtoOnly = [];

    /*
    * this is used to get array data
    * by setting keys that corresspond to
    * property names required in the data
    */
    protected array $dtoDataKeys = [];

    /*
    * this helps set properties that are 
    * files
    */
    protected array $dtoFileKeys = [];
    
    protected function fromRequestExtension(Request $request) : self
    {
        return $this;
    }
    
    protected function fromArrayExtension(array $data) : self
    {
        return $this;
    }
}