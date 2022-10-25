<?php

namespace markhuot\data\attributes;

use Attribute;
use function Symfony\Component\String\u;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapFrom implements MapFromInterface
{
    const CAMEL = '__camel';
    CONST SNAKE = '__snake';

    function __construct(
        protected string $key
    ) {
    }

    function mapFrom(\ReflectionProperty $property): ?string
    {
        if (in_array($this->key, [
            static::CAMEL,
            static::SNAKE,
        ])) {
            return $this->mapCase($property);
        }

        return $this->key ?? $property->getName();
    }

    protected function mapCase(\ReflectionProperty $property): ?string
    {
        $propertyName = $property->getName() ?? '';

        $intermediate = $propertyName;
        $intermediate = preg_replace('/([^a-z])/i', ' ', $intermediate);
        $intermediate = preg_replace('/([A-Z])/', ' $1', $intermediate);

        if ($this->key === static::SNAKE) {
            return (string)u($intermediate)->snake();
        }
        if ($this->key === static::CAMEL) {
            return (string)u($intermediate)->camel();
        }

        return null;
    }
}
