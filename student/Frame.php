<?php

namespace IPP\Student;

class Frame{
    /** @var array<string, Variable> */
    protected array $variables;

    public function __construct()
    {
        $this->variables = [];
    }

    public function getVariable(string $name): Variable
    {
        if (!array_key_exists($name, $this->variables)) {
            // exit program
            exit(54);
        }
        return $this->variables[$name];
    }

    public function addVariable(string $var_name): void
    {
        if (array_key_exists($var_name, $this->variables)) {
            // exit program
            exit(52);
        }
        $this->variables[$var_name] = new Variable($var_name, null);
    }
}