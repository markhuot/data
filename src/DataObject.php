<?php

namespace markhuot\data;

class DataObject
{
    /**
     * @param Array<mixed> $data
     */
    function __construct(...$data)
    {
        (new Data($this))
            ->fill(...$data)
            ->validate();
    }
}