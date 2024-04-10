<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the operand value error
 */
class OperandValueException extends IPPException
{
    public function __construct(string $message = "Error: operand value incorrect", int $code = ReturnCode::OPERAND_VALUE_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
