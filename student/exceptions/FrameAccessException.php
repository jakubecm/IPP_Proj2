<?php

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for the frame access error
 */
class FrameAccessException extends IPPException
{
    public function __construct(string $message = "Error: unable to access frame", int $code = ReturnCode::FRAME_ACCESS_ERROR, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
