<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the value error
 */
class ValueException extends IPPException
{
    public function __construct(string $message = "Error: invalid value", int $code = ReturnCode::VALUE_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
