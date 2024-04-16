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


    /**
     * Gets the value of a variable from the current frame.
     *
     * @param string $name The name of the variable.
     * @return Variable The variable.
     * @throws VariableAccessException If the variable does not exist.
     */
    public function getVariable(string $name): Variable
    {
        if (!array_key_exists($name, $this->variables)) {

            throw new VariableAccessException("Variable $name does not exist");
        }
        return $this->variables[$name];
    }


    /**
     * Adds the variable to the current frame.
     *
     * @param string $var_name The name of the variable.
     * @throws SemanticErrorException If the variable already exists.
     */
    public function addVariable(string $var_name): void
    {
        if (array_key_exists($var_name, $this->variables)) {

            throw new SemanticErrorException("Variable $var_name already exists");
        }
        $this->variables[$var_name] = new Variable($var_name, null);
    }
}
