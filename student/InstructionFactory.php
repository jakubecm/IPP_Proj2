<?php

namespace IPP\Student;
require_once 'RawInstruction.php';

class InstructionFactory
{

    public static function createInstruction(Interpreter $interpret, \DOMElement $xmlElement, string $opcode): RawInstruction
    {
        switch ($opcode) {
            case 'MOVE':
                return new MOVE($interpret, $xmlElement);
                break;
            case 'CREATEFRAME':
                return new CREATEFRAME($interpret, $xmlElement);
                break;
            case 'PUSHFRAME':
                return new PUSHFRAME($interpret, $xmlElement);
                break;
            case 'POPFRAME':
                return new POPFRAME($interpret, $xmlElement);
                break;
            case 'DEFVAR':
                return new DEFVAR($interpret, $xmlElement);
                break;
            case 'CALL':
                return new CALL($interpret, $xmlElement);
                break;
            case 'RETURN':
                return new RETURN_I($interpret, $xmlElement);
                break;
            case 'PUSHS':
                return new PUSHS($interpret, $xmlElement);
                break;
            case 'POPS':
                return new POPS($interpret, $xmlElement);
                break;
            case 'ADD':
                return new ADD($interpret, $xmlElement);
                break;
            case 'SUB':
                return new SUB($interpret, $xmlElement);
                break;
            case 'MUL':
                return new MUL($interpret, $xmlElement);
                break;
            case 'IDIV':
                return new IDIV($interpret, $xmlElement);
                break;
            case 'LT':
                return new LT($interpret, $xmlElement);
                break;
            case 'GT':
                return new GT($interpret, $xmlElement);
                break;
            case 'EQ':
                return new EQ($interpret, $xmlElement);
                break;
            case 'AND':
                return new AND_I($interpret, $xmlElement);
                break;
            case 'OR':
                return new OR_I($interpret, $xmlElement);
                break;
            case 'NOT':
                return new NOT_I($interpret, $xmlElement);
                break;
            case 'INT2CHAR':
                return new INT2CHAR($interpret, $xmlElement);
                break;
            case 'STRI2INT':
                return new STRI2INT($interpret, $xmlElement);
                break;
            case 'READ':
                return new READ($interpret, $xmlElement);
                break;
            case 'WRITE':
                return new WRITE($interpret, $xmlElement);
                break;
            case 'CONCAT':
                return new CONCAT($interpret, $xmlElement);
                break;
            case 'STRLEN':
                return new STRLEN($interpret, $xmlElement);
                break;
            case 'GETCHAR':
                return new GETCHAR($interpret, $xmlElement);
                break;
            case 'SETCHAR':
                return new SETCHAR($interpret, $xmlElement);
                break;
            case 'TYPE':
                return new TYPE($interpret, $xmlElement);
                break;
            case 'LABEL':
                return new LABEL($interpret, $xmlElement);
                break;
            case 'JUMP':
                return new JUMP($interpret, $xmlElement);
                break;
            case 'JUMPIFEQ':
                return new JUMPIFEQ($interpret, $xmlElement);
                break;
            case 'JUMPIFNEQ':
                return new JUMPIFNEQ($interpret, $xmlElement);
                break;
            case 'EXIT':
                return new EXIT_I($interpret, $xmlElement);
                break;
            case 'DPRINT':
                return new DPRINT($interpret, $xmlElement);
                break;
            case 'BREAK':
                return new BREAK_I($interpret, $xmlElement);
                break;
            default:
                echo "Unknown opcode: $opcode\n";
                exit(32);

        }
    }
}


