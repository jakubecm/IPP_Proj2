<?php

namespace IPP\Student;

use IPP\Core\ReturnCode;
use IPP\Student\Exceptions\SourceStructureException;

class Arg
{
    public Interpreter $interpreter;
    public string $type;
    public string|int|bool|null $value;


    public function __construct(Interpreter $interpreter, string $type, string $value)
    {
        $this->interpreter = $interpreter;
        $this->type = $type;
        $this->value = $value;
        $this->castValue();
    }

    /**
     * Casts the value to the correct type.
     * @throws SourceStructureException If the value is not of the correct type.
     */
    private function castValue(): void
    {

        switch ($this->type) {
            case 'int':
                // it can be a decimal, hexadecimal or octal number
                if (preg_match('/^0x[0-9a-f]+$/i', strval($this->value))) {
                    $this->value = intval($this->value, 16);
                    break;
                } elseif (preg_match('/^0[0-7]+$/i', strval($this->value))) {
                    $this->value = intval($this->value, 8);
                    break;
                } elseif (preg_match('/^-?\d+$/', strval($this->value))) {
                    $this->value = intval($this->value);
                    break;
                } else {
                    throw new SourceStructureException;
                }

            case 'bool':
                $this->value = $this->value === 'true';
                break;
            case 'string':
                $this->value = preg_replace_callback('/\\\\\d{3}/', function ($matches) {
                    return chr(intval(substr($matches[0], 1)));
                }, strval($this->value));
                break;
            case 'var':
                // do nothing
                break;
            case 'label':
                // do nothing
                break;
            case 'type':
                $allowedTypes = ['int', 'bool', 'string'];
                if (!in_array($this->value, $allowedTypes)) {
                    throw new SourceStructureException;
                }
                break;
            case 'nil':
                break;
            default:
                throw new SourceStructureException;
        }
    }
}
