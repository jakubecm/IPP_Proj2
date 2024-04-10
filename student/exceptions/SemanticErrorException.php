<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for semantic errors
 */
class SemanticErrorException extends IPPException
{
    public function __construct(string $message = "Error: Semantic error detected", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::SEMANTIC_ERROR, $previous);
    }
}
