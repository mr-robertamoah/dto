<?php

namespace MrRobertAmoah\DTO;

use App\Traits\DTOTrait;
use Illuminate\Http\Request;

abstract class BaseDTO
{
    use DTOTrait;

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
    protected array $dtoKeys = [];

    /*
    * this helps set properties that are 
    * files
    */
    protected array $dtoFiles = [];
    
    public function fromRequestExtension(Request $request)
    {
        return $this;
    }
    
    public function fromArrayExtension(array $data)
    {
        return $this;
    }
}