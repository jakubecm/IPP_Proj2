<?php

namespace IPP\Student;

require_once 'RawInstruction.php';

class InstructionFactory
{

    public static function createInstruction(Interpreter $interpret, \DOMElement $xmlElement, string $opcode): RawInstruction
    {;
        switch (strtoupper($opcode)) {
            case 'MOVE':
                return new MOVE($interpret, $xmlElement);
            case 'CREATEFRAME':
                return new CREATEFRAME($interpret, $xmlElement);
            case 'PUSHFRAME':
                return new PUSHFRAME($interpret, $xmlElement);
            case 'POPFRAME':
                return new POPFRAME($interpret, $xmlElement);
            case 'DEFVAR':
                return new DEFVAR($interpret, $xmlElement);
            case 'CALL':
                return new CALL($interpret, $xmlElement);
            case 'RETURN':
                return new RETURN_I($interpret, $xmlElement);
            case 'PUSHS':
                return new PUSHS($interpret, $xmlElement);
            case 'POPS':
                return new POPS($interpret, $xmlElement);
            case 'ADD':
                return new ADD($interpret, $xmlElement);
            case 'SUB':
                return new SUB($interpret, $xmlElement);
            case 'MUL':
                return new MUL($interpret, $xmlElement);
            case 'IDIV':
                return new IDIV($interpret, $xmlElement);
            case 'LT':
                return new LT($interpret, $xmlElement);
            case 'GT':
                return new GT($interpret, $xmlElement);
            case 'EQ':
                return new EQ($interpret, $xmlElement);
            case 'AND':
                return new AND_I($interpret, $xmlElement);
            case 'OR':
                return new OR_I($interpret, $xmlElement);
            case 'NOT':
                return new NOT_I($interpret, $xmlElement);
            case 'INT2CHAR':
                return new INT2CHAR($interpret, $xmlElement);
            case 'STRI2INT':
                return new STRI2INT($interpret, $xmlElement);
            case 'READ':
                return new READ($interpret, $xmlElement);
            case 'WRITE':
                return new WRITE($interpret, $xmlElement);
            case 'CONCAT':
                return new CONCAT($interpret, $xmlElement);
            case 'STRLEN':
                return new STRLEN($interpret, $xmlElement);
            case 'GETCHAR':
                return new GETCHAR($interpret, $xmlElement);
            case 'SETCHAR':
                return new SETCHAR($interpret, $xmlElement);
            case 'TYPE':
                return new TYPE($interpret, $xmlElement);
            case 'LABEL':
                return new LABEL($interpret, $xmlElement);
            case 'JUMP':
                return new JUMP($interpret, $xmlElement);
            case 'JUMPIFEQ':
                return new JUMPIFEQ($interpret, $xmlElement);
            case 'JUMPIFNEQ':
                return new JUMPIFNEQ($interpret, $xmlElement);
            case 'EXIT':
                return new EXIT_I($interpret, $xmlElement);
            case 'DPRINT':
                return new DPRINT($interpret, $xmlElement);
            case 'BREAK':
                return new BREAK_I($interpret, $xmlElement);
            default:
                echo "Unknown opcode: $opcode\n";
                exit(32);
        }
    }
}
