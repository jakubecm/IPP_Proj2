<?php

namespace IPP\Student;
require_once 'RawInstruction.php';

class InstructionFactory
{

    public static function createInstruction(Interpreter $interpret, \DOMElement $xmlElement, string $opcode): RawInstruction
    {
        switch ($opcode) {
            case 'DEFVAR':
                return new DEFVAR($interpret, $xmlElement);
            case 'WRITE':
                return new WRITE($interpret, $xmlElement);
            default:
                throw new \Exception("Unknown opcode: $opcode");
        }
    }
}


