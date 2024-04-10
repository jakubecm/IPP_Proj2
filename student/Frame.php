<?php

namespace IPP\Student;

use IPP\Student\Exceptions\SemanticErrorException;
use IPP\Student\Exceptions\VariableAccessException;

class Frame
{
    /** @var array<string, Variable> */
    protected array $variables;

    public function __construct()
    {
        $this->variables = [];
    }

    public function getVariable(string $name): Variable
    {
        if (!array_key_exists($name, $this->variables)) {

            throw new VariableAccessException("Variable $name does not exist");
        }
        return $this->variables[$name];
    }

    public function addVariable(string $var_name): void
    {
        if (array_key_exists($var_name, $this->variables)) {

            throw new SemanticErrorException("Variable $var_name already exists");
        }
        $this->variables[$var_name] = new Variable($var_name, null);
    }
}
