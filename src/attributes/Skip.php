<?php

namespace markhuot\data\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class Skip implements MapFromInterface
{
    function mapFrom(\ReflectionProperty $property): ?string
    {
        return null;
    }
}
