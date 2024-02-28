<?php

namespace IPP\Student;


class Arg {
    public Interpreter $interpreter;
    public string $type;
    public string|int|bool $value;


    public function __construct(Interpreter $interpreter, string $type, string $value){
        $this->interpreter = $interpreter;
        $this->type = $type;
        $this->value = $value;
        $this->castValue();
    }

    // write a method that will check the argument type and cast it to the value
    private function castValue(): void{
        // check that the value is not empty
        if(empty($this->value)){
            $this->value = "";
            return;
        }
        switch ($this->type){
            case 'int':
                // it can be a decimal, hexadecimal or octal number
                if(preg_match('/^0x[0-9a-f]+$/i', $this->value)){
                    $this->value = intval($this->value, 16);
                }
                elseif(preg_match('/^0[0-7]+$/i', $this->value)){
                    $this->value = intval($this->value, 8);
                }
                else{
                    $this->value = intval($this->value);
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

            // add cases for var, label, type, nil???
            default:
                throw new \Exception("Unknown type: {$this->type}");
        }
    }

}