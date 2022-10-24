<?php

namespace markhuot\data\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapFromCamel implements MapFromInterface
{
    function mapFrom(string $propertyName): string
    {
        return preg_replace_callback('/([A-Z])/', fn ($m) => '_'.strtolower($m[1]), $propertyName);
    }
}
