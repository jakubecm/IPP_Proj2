<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the operand type error
 */
class OperandTypeException extends IPPException
{
    public function __construct(string $message = "Error: incorrect operand type", int $code = ReturnCode::OPERAND_TYPE_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}