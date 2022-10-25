<?php

namespace markhuot\data\attributes;

use Attribute;
use function Symfony\Component\String\u;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapCase implements MapFromInterface
{
    const CAMEL = 'camel';
    const SNAKE = 'snake';
    const KEBAB = 'Kebab';

    function __construct(
        protected string $from,
        protected string $to,
    ) {  
    }

    function mapFrom(string $propertyName): ?string
    {
        if ($this->to === static::SNAKE) {
            $intermediate = preg_replace('/([^a-z])/i', ' ', $propertyName);
        }
        else if ($this->to === static::CAMEL) {
            $intermediate = preg_replace('/([A-Z])/', ' $1', $propertyName);
        }
        else {
            $intermediate = $propertyName;
        }
        
        if ($this->from === static::SNAKE) {
            return (string)u($intermediate)->snake();
        }
        if ($this->from === static::CAMEL) {
            return (string)u($intermediate)->camel();
        }
        if ($this->from === static::KEBAB) {
            return (string)u($intermediate)->kebab();
        }

        return null;
    }
}
