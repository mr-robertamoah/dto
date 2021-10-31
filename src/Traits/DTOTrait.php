<?php

namespace App\Traits;

use Illuminate\Http\Request;
use ReflectionObject;
use ReflectionProperty;

trait DTOTrait
{
    public ?string $userId = null;
    public ?array $id = null;
    public ?ReflectionObject $dtoReflectionObject = null;

    public static function new()
    {
        return new static;
    }

    public function __call($method, $parameters)
    {
        if ($method === 'addData') {
            return $this->addPropertyValues($parameters);
        }

        $startWith = substr($method, 0, 4);

        if ($startWith === 'with' && count($parameters)) {
            return $this->with($this->getPropertyForWith($method), $parameters[0]);
        }
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
        foreach ($this->dtoReflectionObject as $property) {
            if ($property->getName() === $value) {
                return $property->getValue();
            }
        }

        return null;
    }

    private function isValidProperty($value)
    {
        foreach ($this->dtoReflectionObject as $property) {
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

    public static function createFromRequest(Request $request)
    {
        $self = new static;

        if (property_exists($self, 'user')) {
            $self->user = $request->user();
        } else {
            $self->userId = $request->user()?->id;
        }

        $self = $self->setMainPropertyId($self, $request);

        $self = $self->setOtherProperties($self, $request);

        if (method_exists($self, 'createFromRequestExtension')) {
            return $self->createFromRequestExtension($request);
        }

        return $self;
    }

    private function setOtherProperties($self, $request)
    {
        $self = $this->setReflectionObject($self);

        $input = $request->toArray();

        foreach ($self->dtoReflectionObject->getProperties() as $property) {
            $self = $this->with($property, $this->getInputValue($input, $property));
        }

        return $self;
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

    private function setMainPropertyId($self, $request)
    {
        $property = strstr(class_basename($self::class), 'DTO', true);

        $property = $this->format($property) . 'Id';

        if ($property === 'userId') {
            return $self;
        }

        $self->$property = $request->$property;

        return $self;
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
        $isReflectionProperty = !is_string($property);

        if (!property_exists($this, $isReflectionProperty ? $property->name : $property)) {
            return;
        }

        if (! $isReflectionProperty) {
            
            $this->$property = $parameter;

            return $this;
        }
        
        $type = $property->getType();
        $propertyName = $property->name;

        if ($this->propertyIsExcluded($propertyName)) {
            return $this;
        }

        if (! is_null($parameter)) {
            $this->$propertyName = $this->setPropertyBasedOnType($type, $parameter);

            return $this;
        }

        if ($type->allowsNull()) {
            $this->$propertyName = $parameter;

            return $this;
        }

        if (
            ! $type->allowsNull() && 
            $property->isInitialized($this)
        ) {
            return $this;
        }

        if (
            ! $type->allowsNull() && 
            ! $property->isInitialized($this)
        ) {
            $this->$propertyName = $this->getPropertyDefaultBasedOnTypeName($type->getName());

            return $this;
        }

        return $this;
    }

    public function propertyIsExcluded($propertyName)
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

    private function getPropertyDefaultBasedOnTypeName($typeName)
    {
        if ($typeName === 'array') {
            return [];
        }
        
        if ($typeName === 'object') {
            return new ReflectionObject(null);
        }
        
        if ($typeName === 'integer') {
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
