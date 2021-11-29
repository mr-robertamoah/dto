<?php

namespace MrRobertAmoah\DTO\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use MrRobertAmoah\DTO\Exceptions\DTOMethodNotFound;
use ReflectionObject;
use ReflectionProperty;
use Illuminate\Support\Str;
use MrRobertAmoah\DTO\BaseDTO;
use MrRobertAmoah\DTO\Exceptions\DTOPropertyNotFound;
use MrRobertAmoah\DTO\Exceptions\DTOWrongArgument;
use ReflectionUnionType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait DTOTrait
{
    public ?ReflectionObject $dtoReflectionObject = null;

    private bool $forcePropertyOnDTO = false;

    private bool $testFile = false;

    private array $reservedProperties = [
        'dtoReflectionObject',
    ];

    private static array $methodsToSetAndAppendValuesToProperties = [
        'setdatakeys',
        'appenddatakeys',
        'setfilekeys',
        'appendfilekeys',
    ];

    public function __get($property)
    {
        if ($this->isValidProperty($property)) {
            return $this->getPropertyDefaultValueBasedOnType($this->getPropertyType($property));
        }

        if ($this->suppressException('property')) {
            return null;
        }
        
        throw new DTOPropertyNotFound("{$property} property does not exist on this DTO.");
    }

    public static function __callStatic($method, $arguments)
    {
        $methodInLowercase = strtolower($method);
        
        if ($methodInLowercase === 'fromrequest') {

            if (!isset($arguments[0]) || gettype($arguments[0]) !== 'object' || $arguments[0]::class !== Request::class) {
                $class = Request::class;

                throw new DTOWrongArgument("{$method} requires an object of {$class} class as a parameter.");
            }

            return static::new()->createDTOFromRequest($arguments[0], isset($arguments[1]) ? $arguments[1] : 'toArray');
        }

        if ($methodInLowercase === 'fromarray') {

            if (!isset($arguments[0]) || gettype($arguments[0]) !== 'array') {
                $class = Request::class;

                throw new DTOWrongArgument("{$method} requires an array as the only argument.");
            }

            return static::new()->createDTOFromArray($arguments[0]);
        }
        
        if ((new static)->suppressException('method')) {
            return new static;
        }

        throw new DTOMethodNotFound("{$method} static method was not found on this dto.");
    }

    public function __call($method, $arguments)
    {
        $methodInLowercase = strtolower($method);

        if ($methodInLowercase === 'adddata') {
            return $this->clone()->addPropertyValues($arguments[0]);
        }

        if (substr($methodInLowercase, 0, 4) === 'with' && count($arguments)) {
            return $this->clone()->withProperty($this->getPropertyForWith($method),$arguments[0]);
        }

        if ($methodInLowercase === 'forceproperty') {
            return $this->setForceProperty();
        }

        if (in_array($methodInLowercase, static::$methodsToSetAndAppendValuesToProperties)) {
            
            if (!count($arguments)) {
               throw new DTOWrongArgument("a string or array argument is required for this method");
            }

            return $this->setOrAppend($method, $arguments[0]);
        }

        if ($methodInLowercase === 'fromrequest') {

            if (!isset($arguments[0]) || gettype($arguments[0]) !== 'object' || $arguments[0]::class !== Request::class) {
                $class = Request::class;

                throw new DTOWrongArgument("{$method} requires an object of {$class} class as a parameter.");
            }

            return $this->createDTOFromRequest($arguments[0], isset($arguments[1]) ? $arguments[1] : 'toArray');
        }

        if ($methodInLowercase === 'fromarray') {

            if (!isset($arguments[0]) || gettype($arguments[0]) !== 'array') {
                $class = Request::class;

                throw new DTOWrongArgument("{$method} requires an array as the only argument.");
            }

            return $this->createDTOFromArray($arguments[0]);
        }

        if ($methodInLowercase === 'getdatakeys') {
            return $this->dtoDataKeys;
        }

        if ($methodInLowercase === 'getfilekeys') {
            return $this->dtoFileKeys;
        }
        
        if ($this->suppressException('method')) {
            return $this;
        }

        throw new DTOMethodNotFound("{$method} method was not found on this dto.");
    }

    public static function new()
    {
        return new static;
    }

    private function clone()
    {
        return clone $this;
    }

    private function withProperty($property, $value)
    {
        return $this->with($property, $value);
    }

    private function getPropertyType($property)
    {
        $type = (new ReflectionProperty($this, $property))->getType();

        if ($type::class === ReflectionUnionType::class) {
           return $this->getStringedTypesFromUnionType($type);
        }

        return $type->getName();
    }

    private function getStringedTypesFromUnionType(ReflectionUnionType $type)
    {
        return implode(', ', array_map(fn($namedType) => $namedType->getName(), $type->getTypes()));
    }

    private function createDTOFromRequest($request, $method)
    {
        if ($this->isPropertyNotExcluded('user') && $this->isValidProperty('user')) {
            $this->user = $request->user();
        } 
        
        if ($this->isPropertyNotExcluded('userId') && $this->isValidProperty('userId')) {
            $this->userId = $request->user() ? $request->user()?->id : $this->getPropertyDefaultValueBasedOnType(
                $this->getPropertyType('userId')
            );
        }

        $this->setReflectionObject();
        
        $this->setMainPropertyId($request);
        
        $this->setOtherProperties($request, $method);
        
        $this->setFileProperties($request);

        $this->inInitializePublicProperties();

        if (method_exists($this, 'fromRequestExtension')) {
            return $this->fromRequestExtension($request) ?: $this;
        }

        return $this;
    }

    private function createDTOFromArray(array $data)
    {
        $this->setReflectionObject();
        
        foreach($data as $key => $value)
        {
            $this->with($key, $value);
        }

        $this->inInitializePublicProperties();

        if (method_exists($this, 'fromArrayExtension')) {
            return $this->fromArrayExtension($data) ?: $this;
        }

        return $this;
    }

    public function inInitializePublicProperties()
    {
        foreach ($this->dtoReflectionObject->getProperties() as $reflectionProperty) {
            if (
                !$reflectionProperty->isPublic() ||
                $reflectionProperty->isInitialized($this) ||
                $reflectionProperty->isStatic()
            ) {
                continue;
            }

            $property = $reflectionProperty->getName();

            $this->$property = $this->getPropertyDefaultValueBasedOnType($reflectionProperty->getType());
        }
    }

    private function setForceProperty()
    {
        $this->forcePropertyOnDTO = true;

        return $this;
    }

    private function setReflectionObject($object = null)
    {
        if ($object) {
            $object->dtoReflectionObject = new ReflectionObject($object);

            return $object;
        }

        if (!is_null($this->dtoReflectionObject)) {
            return $this;
        }

        $this->dtoReflectionObject = new ReflectionObject($this);

        return $this;
    }

    public function getData(bool $filled = false) : array
    {
        $dtoDataKeys = $this->isValidProperty('dtoDataKeys') ? $this->dtoDataKeys : [];

        if (!count($dtoDataKeys) && $filled) {
            return $this->getFilledData();
        }

        $this->setReflectionObject();

        $data = [];

        foreach ($dtoDataKeys as $value) {

            $data[$value] = $this->getValueForProperty($value);
        }

        return $data;
    }

    public function getFiles()
    {
        $this->setReflectionObject();

        $data = [];

        foreach ($this->isValidProperty('dtoFileKeys') ? $this->dtoFileKeys : [] as $property) {

            $data[$property] = $this->getValueForProperty($property);
        }

        return $data;
    }

    public function getFilledData()
    {
        $this->setReflectionObject();

        $data = [];

        foreach ($this->dtoReflectionObject->getProperties() as $property) {
            if (
                $this->isPropertyExcluded($property->getName()) ||
                !$property->isPublic() ||
                !$property->isInitialized($this)
            ) {
                continue;
            }

            $data[$property->getName()] = $property->getValue($this);
        }

        return $data;
    }

    public function getAllData()
    {
        return [
            ...$this->getData(),
            ...$this->getFiles()
        ];
    }

    private function createProperty(string $property)
    {
        if (property_exists($this, $property)) {
            return;
        }

        $this->$property = null;
    }

    private function setOrAppend($method, $argument)
    {
        $call = strtolower(substr($method, 0, 3));
        $property = substr($method, 3);
        $property = "dto" . strtoupper(substr($property, 0, 1)) . substr($property, 1);

        return $this->$call($property, $argument);
    }

    private function set($property, array|string $keys)
    {
        $this->createProperty($property);

        $this->$property = is_string($keys) ? array_map(fn($item)=> trim($item), explode(',', $keys)) : $keys;

        return $this;
    }

    private function append($property, array|string $keys)
    {
        $this->createProperty($property);

        if (is_string($keys)) {
            $keys = array_map(fn($item)=> trim($item), explode(',', $keys));
        }

        $this->$property = array_merge($this->$property, $keys);

        return $this;
    }

    private function getValueForProperty($value)
    {
        foreach ($this->dtoReflectionObject->getProperties() as $property) {
            if ($property->getName() !== $value) {
                continue;
            }

            return $property->getValue($this);
        }

        return null;
    }

    private function isValidProperty($value)
    {
        if (in_array($value, $this->reservedProperties)) {
            return false;
        }
        
        if (in_array(
            $value, 
            array_map(
                fn($property)=>$property->getName(), 
                $this->dtoReflectionObject?->getProperties() ?: []    
            )
        )) {
            return true;
        }

        return false;
    }

    private function isInvalidProperty($value)
    {
        return ! $this->isValidProperty($value);
    }

    private function propertyShouldBeForced()
    {
        if ($this->forcePropertyOnDTO) {
            return true;
        }

        return config('dto.forcePropertyOnDTO', false);
    }

    private function propertyShouldNotBeForced()
    {
        return !$this->propertyShouldBeForced();
    }

    private function setOtherProperties($request, $method)
    {
        $input = $this->getRequestData($request, $method);

        foreach ($this->dtoReflectionObject->getProperties() as $property) {
            $this->with($property, $this->getInputValue($input, $property));
        }

        return $this;
    }

    private function setFileProperties($request)
    {
        foreach ($this->isValidProperty('dtoFileKeys') ? $this->dtoFileKeys : [] as $filePropertyName) {
            if (! $request->hasFile($filePropertyName)) {
                continue;
            }

            if ($this->isInvalidProperty($filePropertyName) && $this->dontSuppressException('property')) {
                throw new DTOPropertyNotFound("{$filePropertyName} property, which is for files was not set properly");
            }

            $this->addFile($filePropertyName, $request->file($filePropertyName));
        }

        return $this;
    }

    private function testFile()
    {
        $this->testFile = true;
    }

    private function dontTestFile()
    {
        $this->testFile = false;
    }

    private function addFile($property, array|UploadedFile|string $file)
    {
        try {
            if ($file instanceof UploadedFile) {
                $this->$property = $file;

                return $this;
            }
            
            if (is_string($file)) {
                $this->$property = $this->makeFile($file);

                return $this;
            }
    
            $this->$property = array_map(function($f){
                if ($f instanceof UploadedFile) {
                    return $f;
                }

                if (is_string($f)) {
                    return $this->makeFile($f);
                }

                $class = UploadedFile::class;

                throw new DTOWrongArgument("items in the array given must be either a string or a {$class} object");
            }, $file);
        } catch (\Throwable $th) {
            throw new DTOWrongArgument("sorry! you might have tried to assign a non file value to a file property.");
        }
    }

    private function makeFile($file)
    {
        if (File::exists($file)) {
            throw new DTOWrongArgument("{$file} does not exist");
        }
        
        return new UploadedFile(
            $file,
            File::name($file),
            File::mimeType($file),
            null,
            $this->testFile
        );
    }

    private function suppressException(string $type)
    {
        $types = ['property', 'method'];

        if (! in_array($type, $types) || ! File::exists(config_path('dto.php'))) {
            return false;
        }

        return config("dto.suppress{$this->format($type)}NotFoundException", false);
    }

    private function dontSuppressException(string $type)
    {
        return ! $this->suppressException($type);
    }

    private function getRequestData($request, $method = 'toArray')
    {
        if (method_exists($this, 'requestToArray')) {
            return $this->requestToArray($request);
        }
        
        if (property_exists($this, $method)) {
            return $this->$method;
        }

        return $request->toArray();
    }

    private function getInputValue(array $input, ReflectionProperty $property)
    {
        $keys = array_keys($input);

        if (in_array($property->name, $keys)) {
            return $input[$property->name];
        }

        
        if (in_array($property->name, $this->lowercaseKeys($keys))) {
            return $this->getWithLowercase($input, $property->name);
        }

        if (in_array(strtolower($property->name), $keys)) {
            return $input[strtolower($property->name)];
        }

        return null;
    }

    private function getWithLowercase($input, $propertyName)
    {
        foreach ($input as $key => $value) {
            ray($key, $value)->gray();
            if (strtolower($key) === $propertyName) {
                return $value;
            }
        }

        return null;
    }

    private function lowercaseKeys($keys)
    {
        return array_map(fn ($key) => strtolower($key), $keys);
    }

    private function getPropertyWithoutDTOFromBasename($basename)
    {
        return ! Str::beforeLast(strtolower($basename), 'dto') ? $basename : substr($basename, 0, strlen($basename) - 3);
    }

    private function setMainPropertyId($request)
    {
        $basename = class_basename($this::class);

        $property = $this->format($this->getPropertyWithoutDTOFromBasename($basename)) . 'Id';

        if ($property === 'userId' || $this->isPropertyExcluded($property)) {
            return $this;
        }

        $this->$property = $request->$property;

        return $this;
    }

    private function getPropertyForWith($method)
    {
        $property = substr($method, 4);

        return $this->format($property);
    }

    private function format($string)
    {
        return strtolower(substr($string, 0, 1)) . substr($string, 1);
    }

    private function isFileProperty($property)
    {
        return $this->isValidProperty('dtoFileKeys') && in_array($property, $this->dtoFileKeys);
    }

    private function with(ReflectionProperty|string $property, $parameter)
    {
        $propertyName = is_string($property) ? $property : $property->name;
        $isNotProperty = ! property_exists($this, $propertyName);

        if ($this->isFileProperty($propertyName) && !is_null($parameter)) {
            return $this->addFile($propertyName, $parameter);
        }

        if ($isNotProperty && $this->dontSuppressException('property')) {
            throw new DTOPropertyNotFound("{$propertyName} property was not properly set on this dto");
        }

        if (
            $this->isPropertyExcluded($propertyName) ||
            ($this->isInvalidProperty($propertyName) && $this->propertyShouldNotBeForced())
        ) {
            return $this;
        }

        if (is_string($property)) {
            
            $this->$property = $parameter;

            return $this;
        }

        if (! is_null($parameter)) {
            $this->$propertyName = $this->setPropertyBasedOnType($property->getType(), $parameter);

            return $this;
        }

        if ($property->getType()?->allowsNull()) {
            $this->$propertyName = $parameter;

            return $this;
        }

        if ($property->isPublic() && $property->isInitialized($this)) {
            return $this;
        }

        $this->$propertyName = $this->getPropertyDefaultValueBasedOnType($this->getPropertyType($propertyName));

        return $this;
    }

    private function isPropertyNotExcluded($propertyName)
    {
        return !$this->isPropertyExcluded($propertyName);
    }

    private function isPropertyExcluded($propertyName)
    {
        if (count($dtoOnly = $this->dtoOnly ?: [])) {
            return !in_array($propertyName, $dtoOnly);
        }

        $class = BaseDTO::class;

        if (in_array(
            $propertyName, 
            array_merge(
                $this->dtoExclude ?: [], 
                defined("{$class}::EXCLUDED") ? self::EXCLUDED : []
            ) //exclude dataKeys and others
        )) {
            return true;
        }

        return false;
    }

    private function setPropertyBasedOnType($type, $parameter)
    {
        $typeName = method_exists($type, 'getName') ? $type->getName() : $this->getStringedTypesFromUnionType($type);

        if (str_contains($typeName, 'array')) {
            return is_array($parameter) ? $parameter : (array) json_decode($parameter);
        }

        if (str_contains($typeName, 'object')) {
            return is_object($parameter) ? $parameter : (object) json_decode($parameter);
        }

        if (str_contains($typeName, 'int')) {
            return (int) $parameter;
        }

        if (str_contains($typeName, 'float') || str_contains($typeName, 'double')) {
            return (float) $parameter;
        }

        return $parameter;
    }

    private function getPropertyDefaultValueBasedOnType($type)
    {
        if ($type === null) {
            return null;
        }

        if (str_contains($type, 'array')) {
            return [];
        }
        
        if (str_contains($type, 'object')) {
            return (object) [];
        }
        
        if (str_contains($type, 'float') || str_contains($type, 'double')) {
            return 0.0;
        }
        
        if (str_contains($type, 'int')) {
            return 0;
        }

        return '';
    }

    private function addPropertyValues($parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->with($key, $value);
        }

        return $this;
    }
}
