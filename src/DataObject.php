<?php

namespace markhuot\data;

class DataObject
{
    function __construct(...$data)
    {
        (new Data($this))
            ->fill(...$data)
            ->validate();
    }
}