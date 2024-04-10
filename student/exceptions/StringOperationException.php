<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the string operation error
 */
class StringOperationException extends IPPException
{
    public function __construct(string $message = "Error: string operation invalid", int $code = ReturnCode::STRING_OPERATION_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}