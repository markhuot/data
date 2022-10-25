<?php

namespace markhuot\data\exceptions;

use Symfony\Component\Validator\ConstraintViolationList;

class ValidationException extends \Exception
{
    protected ConstraintViolationList $violations;

    function __construct(ConstraintViolationList $violations, ...$attr)
    {
        $this->violations = $violations;
        parent::__construct(...$attr);
    }
}