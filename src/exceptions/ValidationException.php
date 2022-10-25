<?php

namespace markhuot\data\exceptions;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    protected ConstraintViolationListInterface $violations;

    function setViolations(ConstraintViolationListInterface $violations): self
    {
        $this->violations = $violations;

        return $this;
    }

    function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
    
}