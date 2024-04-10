<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the variable access error
 */
class VariableAccessException extends IPPException
{
    public function __construct(string $message = "Error: cannot access variable", int $code = ReturnCode::VARIABLE_ACCESS_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
