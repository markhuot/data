<?php

namespace markhuot\data\attributes;

interface MapFromInterface
{
    function mapFrom(string $propertyName): string;
}
