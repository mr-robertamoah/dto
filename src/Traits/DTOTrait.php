<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use MrRobertAmoah\DTO\Exceptions\DTOMethodNotFound;
use ReflectionObject;
use ReflectionProperty;
use Illuminate\Support\Str;
use MrRobertAmoah\DTO\Exceptions\DTOPropertyNotFound;

trait DTOTrait
{
    public ?string $userId = null;
    public ?array $id = null;
    public ?ReflectionObject $dtoReflectionObject = null;

    private bool $forcePropertyOnDTO = false;

    public function __callStatic($method, $arguments)
    {
        if (strtolower($method) === 'forceproperty') {
            return (new static)->setForceProperty();
        }
        
        if ($this->suppressException('method')) {
            return new static;
        }

        throw new DTOMethodNotFound("{$method} static method was not found on this dto.");
    }

    public function __call($method, $arguments)
    {
        if ($method === 'addData') {
            return $this->addPropertyValues($arguments);
        }

        if (substr($method, 0, 4) === 'with' && count($arguments)) {
            return $this->with($this->getPropertyForWith($method), $arguments[0]);
        }

        if (strtolower($method) === 'forceproperty') {
            return $this->setForceProperty();
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

    public static function fromRequest(Request $request, string $method = 'toArray')
    {
        static::new()->createDTOFromRequest($request, $method);
    }

    public static function fromArray(array $data)
    {
        
    }

    private function createDTOFromRequest($request, $method)
    {
        if (property_exists($this, 'user')) {
            $this->user = $request->user();
        } else {
            $this->userId = $request->user()?->id;
        }

        $this->setMainPropertyId($request);

        $this->setOtherProperties($request, $method);

        $this->setFileProperties($request);

        if (method_exists($this, 'fromRequestExtension')) {
            return $this->fromRequestExtension($request) ?: $this;
        }

        return $this;
    }

    private function createDTOFromArray(array $data)
    {
        foreach($data as $key => $value)
        {
            $this->with($key, $value);
        }

        if (method_exists($this, 'fromArrayExtension')) {
            return $this->fromArrayExtension($data) ?: $this;
        }

        return $this;
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

    public function getData()
    {
        $this->setReflectionObject();

        $data = [];

        foreach ($this->isValidProperty('dtoKeys') ? $this->dtoKeys : [] as $value) {

            $data[$value] = $this->getValueForProperty($value);
        }

        return $data;
    }

    private function getValueForProperty($value)
    {
        foreach ($this->dtoReflectionObject->getProperties() as $property) {
            if ($property->getName() !== $value) {
                continue;
            }

            return $property->getValue();
        }

        return null;
    }

    private function isValidProperty($value)
    {
        foreach ($this->dtoReflectionObject?->getProperties() ?: [] as $property) {
            if ($property->getName() === $value) {
                return true;
            }
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
        $this->setReflectionObject();

        $input = $this->getRequestData($request, $method);

        foreach ($this->dtoReflectionObject->getProperties() as $property) {
            $this->with($property, $this->getInputValue($input, $property));
        }

        return $this;
    }

    private function setFileProperties($request)
    {
        foreach ($this->isValidProperty('dtoFiles') ? $this->dtoFiles : [] as $filePropertyName) {
            if (! $request->hasFile($filePropertyName)) {
                continue;
            }

            if ($this->isInvalidProperty($filePropertyName) && $this->dontSuppressException('property')) {
                throw new DTOPropertyNotFound("{$filePropertyName} property, which is for files was not set properly");
            }

            $this->$filePropertyName = $request->file($filePropertyName);
        }

        return $this;
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
        return ! Str::endsWith(strtolower($basename), 'dto') ? $basename : strstr(strtolower($basename), 'dto', true);
    }

    private function setMainPropertyId($request)
    {
        $basename = class_basename($this::class);

        $property = $this->format($this->getPropertyWithoutDTOFromBasename($basename)) . 'Id';

        if ($property === 'userId') {
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

    private function with($property, $parameter)
    {
        $isNotProperty = ! property_exists($this, $property->name ?: $property);

        if ($isNotProperty && $this->suppressException('property')) {
            return $this;
        }

        if ($isNotProperty) {
            $property = $property->name ? : $property;

            throw new DTOPropertyNotFound("{$property} property was not properly set on this dto");
        }

        if ($this->isPropertyExcluded($property->name ?: $property)) {
            return $this;
        }

        if ($this->isInvalidProperty($property->name ?: $property) && $this->propertyShouldNotBeForced()) {
            return $this;
        }

        if (is_string($property)) {
            
            $this->$property = $parameter;

            return $this;
        }
        
        $type = $property->getType();
        $propertyName = $property->name;

        if (! is_null($parameter)) {
            $this->$propertyName = $this->setPropertyBasedOnType($type, $parameter);

            return $this;
        }

        if ($type->allowsNull()) {
            $this->$propertyName = $parameter;

            return $this;
        }

        if ($property->isInitialized($this)) {
            return $this;
        }

        $this->$propertyName = $this->getPropertyDefaultValueBasedOnType($type->getName());

        return $this;
    }

    public function isPropertyExcluded($propertyName)
    {
        if (in_array($propertyName, property_exists($this, 'dtoExclude') ? $this->dtoExclude : [])) {
            return true;
        }

        return false;
    }

    private function setPropertyBasedOnType($type, $parameter)
    {
        $typeName = $type->getName();

        if ($typeName === 'array') {
            return is_array($parameter) ? $parameter : (array) json_decode($parameter);
        }

        if ($typeName === 'object') {
            return is_object($parameter) ? $parameter : (object) json_decode($parameter);
        }

        if (str_contains($typeName, 'int')) {
            return (int) $parameter;
        }

        if (str_contains($typeName, 'float')) {
            return (float) $parameter;
        }

        return $parameter;
    }

    private function getPropertyDefaultValueBasedOnType($type)
    {
        if ($type === 'array') {
            return [];
        }
        
        if ($type === 'object') {
            return new ReflectionObject(null);
        }
        
        if ($type === 'integer') {
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
