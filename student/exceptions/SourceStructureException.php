<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for invalid source structure
 */
class SourceStructureException extends IPPException
{
    public function __construct(string $message = "Error: Invalid source code structure", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE, $previous);
    }
}
