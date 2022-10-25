<?php

namespace markhuot\data;

use Illuminate\Support\Arr;
use markhuot\data\attributes\MapFromInterface;
use markhuot\data\exceptions\ValidationException;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use ReflectionNamedType;
use Symfony\Component\Validator\Validation;

class Data
{
    function __construct(
        protected object $obj,
    ) {
    }

    /**
     * @param Array<mixed> $data
     */
    function fill(array $data = []): self
    {
        $reflect = new \ReflectionClass($this->obj);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $key = $this->mapKey($property);
            if ($key === null) {
                continue;
            }
            
            $value = Arr::get($data, $key, $property->getDefaultValue());
            $this->obj->{$property->getName()} = $this->mapValue($property, $value);
        }

        return $this;
    }

    protected function mapKey(\ReflectionProperty $property): ?string
    {
        $propertyName = $property->getName();

        // Check if the property has an attribute that remaps the source
        foreach ($property->getAttributes() as $attribute) {
            /** @var MapFromInterface $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            $reflect = new \ReflectionClass($attributeInstance);
            if ($reflect->implementsInterface(MapFromInterface::class)) {
                return $attributeInstance->mapFrom($property);
            }
        }

        // Check if the class has an attribute that remaps all source properties
        foreach ($property->getDeclaringClass()->getAttributes() as $attribute) {
            /** @var MapFromInterface $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            $reflect = new \ReflectionClass($attributeInstance);
            if ($reflect->implementsInterface(MapFromInterface::class)) {
                return $attributeInstance->mapFrom($property);
            }
        }

        return $property->getName();
    }

    protected function mapValue(\ReflectionProperty $property, mixed $value): mixed
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

        if ($isArray && is_array($value)) {
            return array_map(function ($item) use ($type, $isOptional) {
                return $this->mapValueItem($type, $isOptional, $item);
            }, $value);
        }

        return $this->mapValueItem($type, $isOptional, $value);
    }

    protected function mapValueItem(string|null $type, bool $isOptional, mixed $value): mixed
    {
        if ($isOptional && $value === null) {
            return $value;
        }

        if ($type) {
            if (class_exists($type) && is_array($value)) {
                $reflect = new \ReflectionClass($type);
                return (new self($reflect->newInstance()))->fill($value)->validate()->get();
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

        if ($type === 'int' && is_numeric($value)) {
            return (int)$value;
        }

        if ($type === 'float' && is_numeric($value)) {
            return (float)$value;
        }

        return $value;
    }

    /**
     * @return ?array{0: ?string, 1: bool, 2: bool}
     */
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
                /** @var Var_ $var */
                $var = $reflect->getTagsByName('var')[0];
                $varType = $var->getType();
                if ($varType && is_a($varType, Array_::class)) {
                    $isArray = true;
                    /** @var Array_ $varType */
                    $var = $varType->getValueType();
                }
                if (is_a($var, Object_::class)) {
                    /** @var Object_ $var */
                    $type = (string)$var->getFqsen();
                }
            }
        }

        if ($type) {
            return [$type, $isArray, $isOptional];
        }

        return null;
    }

    /**
     * @return ?array{0: ?string, 1: bool, 2: bool}
     */
    protected function parsePhpForType(\ReflectionProperty $property)
    {
        $type = null;
        $isArray = false;
        $isOptional = false;

        $phpType = $property->getType();
        if (!empty($phpType) && is_a($phpType, ReflectionNamedType::class)) {
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

    function validate(): self
    {
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $errors = $validator->validate($this->obj);
        if (count($errors)) {
            throw (new ValidationException())->setViolations($errors);
        }

        return $this;
    }

    function get(): mixed
    {
        return $this->obj;
    }
}
