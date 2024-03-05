<?php

namespace IPP\Student;
use IPP\Core\ReturnCode;


class Arg {
    public Interpreter $interpreter;
    public string $type;
    public string|int|bool|null $value;


    public function __construct(Interpreter $interpreter, string $type, string $value){
        $this->interpreter = $interpreter;
        $this->type = $type;
        $this->value = $value;
        $this->castValue();
    }

    // write a method that will check the argument type and cast it to the value
    private function castValue(): void{

        switch ($this->type){
            case 'int':
                // it can be a decimal, hexadecimal or octal number
                if(preg_match('/^0x[0-9a-f]+$/i', $this->value)){
                    $this->value = intval($this->value, 16);
                    break;
                }
                elseif(preg_match('/^0[0-7]+$/i', $this->value)){
                    $this->value = intval($this->value, 8);
                    break;
                }
                elseif(preg_match('/^-?\d+$/', $this->value)){
                    $this->value = intval($this->value);
                    break;
                }
                else{
                    echo "Invalid int value: {$this->value}\n";
                    exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                
            case 'bool':
                $this->value = $this->value === 'true';
                break;
            case 'string':
                $this->value = preg_replace_callback('/\\\\\d{3}/', function ($matches) {
                    return chr(intval(substr($matches[0], 1)));
                }, $this->value);
                break;
            case 'var':
                // do nothing
                break;
            case 'label':
                // do nothing
                break;
            case 'type':
                $allowedTypes = ['int', 'bool', 'string'];
                if(!in_array($this->value, $allowedTypes)){
                    echo "Invalid type value: {$this->value}\n";
                    exit(ReturnCode::INVALID_SOURCE_STRUCTURE);
                }
                break;
            case 'nil':
                break;

            // add cases for var, label, type, nil???
            default:
                throw new \Exception("Unknown type: {$this->type}");
        }
    }

}