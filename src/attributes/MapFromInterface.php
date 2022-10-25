<?php

namespace markhuot\data\attributes;

interface MapFromInterface
{
    function mapFrom(\ReflectionProperty $property): ?string;
}
