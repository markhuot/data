<?php

namespace markhuot\data\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapFrom implements MapFromInterface
{
    function __construct(
        protected string $key
    ) {
    }

    function mapFrom(string $propertyName): string
    {
        return $this->key;
    }
}
