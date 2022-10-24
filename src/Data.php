<?php

namespace markhuot\data;

use Illuminate\Support\Arr;
use markhuot\data\attributes\MapFromInterface;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;

class DataObject
{
    function __construct(array $data = [])
    {
        $reflect = new \ReflectionClass($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $key = $this->mapKey($property);
            $value = Arr::get($data, $key, $property->getDefaultValue());
            $this->{$property->getName()} = $this->mapValue($property, $value);
        }
    }

    protected function mapKey(\ReflectionProperty $property)
    {
        $propertyName = $property->getName();

        // Check if the property has an attribute that remaps the source
        foreach ($property->getAttributes() as $attribute) {
            $reflect = new \ReflectionClass($attribute->newInstance());
            if ($reflect->implementsInterface(MapFromInterface::class)) {
                return $attribute->newInstance()->mapFrom($propertyName);
            }
        }

        // Check if the class has an attribute that remaps all source properties
        foreach ($property->getDeclaringClass()->getAttributes() as $attribute) {
            $reflect = new \ReflectionClass($attribute->newInstance());
            if ($reflect->implementsInterface(MapFromInterface::class)) {
                return $attribute->newInstance()->mapFrom($propertyName);
            }
        }

        return $property->getName();
    }

    protected function mapValue(\ReflectionProperty $property, mixed $value)
    {
        if ($commentType = $this->parseDocBlockForType($property)) {
            [$type, $isArray, $isOptional] = $commentType;
        }

        else if ($phpType = $this->parsePhpForType($property)) {
            [$type, $isArray, $isOptional] = $phpType;
        }

        else {
            $type = null;
            $isArray = false;
            $isOptional = false;
        }

        if ($isArray) {
            return array_map(function ($item) use ($type, $isOptional) {
                return $this->mapValueItem($type, $isOptional, $item);
            }, $value);
        }

        return $this->mapValueItem($type, $isOptional, $value);
    }

    protected function mapValueItem(string|null $type, bool $isOptional, mixed $value)
    {
        if ($isOptional && $value === null) {
            return $value;
        }

        if ($type) {
            if (class_exists($type)) {
                $reflect = new \ReflectionClass($type);
                if ($reflect->isSubclassOf(self::class)) {
                    return $reflect->newInstance($value);
                }
            }
        }

        if ($type === 'DateTime' && is_string($value)) {
            return new \DateTime($value);
        }
        else if ($type === 'DateTime' && is_numeric($value)) {
            return new \DateTime('@' . $value);
        }


        if ($type === 'bool' || $type === 'boolean') {
            return $value === '1' || $value === 'true' || $value === 1 || $value === true;
        }

        if ($type === 'int') {
            return (int)$value;
        }

        if ($type === 'float') {
            return (float)$value;
        }

        return $value;
    }

    protected function parseDocBlockForType(\ReflectionProperty $property)
    {
        $type = null;
        $isArray = false;
        $isOptional = false;

        $docComment = $property->getDocComment();
        if ($docComment) {
            $contextFactory = new ContextFactory();
            $reflect = DocBlockFactory::createInstance()->create($property, $contextFactory->createFromReflector($property));
            if ($reflect->hasTag('var')) {
                $var = $reflect->getTagsByName('var')[0];
                if (is_a($var->getType(), Array_::class)) {
                    $isArray = true;
                    $var = $var->getType()->getValueType();
                }
                if (is_a($var, Object_::class)) {
                    $type = (string)$var->getFqsen();
                }
            }
        }

        if ($type) {
            return [$type, $isArray, $isOptional];
        }

        return null;
    }

    protected function parsePhpForType(\ReflectionProperty $property)
    {
        $type = null;
        $isArray = false;
        $isOptional = false;

        if (!empty($property->getType())) {
            $phpType = $property->getType();
            $phpTypeName = $phpType->getName();
            $isOptional = $phpType->allowsNull();

            if ($phpTypeName === 'array') {
                $isArray = true;
            }
            else if (!empty($phpTypeName) && class_exists($phpTypeName)) {
                $type = $phpTypeName;
            }
            else {
                $type = $phpTypeName;
            }
        }

        if ($type) {
            return [$type, $isArray, $isOptional];
        }

        return null;
    }
}
