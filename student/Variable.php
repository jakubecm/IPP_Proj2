<?php

namespace IPP\Student;

class Variable{

    private string|null $name;
    private string|null $type;
    private string|int|bool|null $value;

    public function __construct(string|null $name, string|null $type, string|int|bool|null $value = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string|int|bool|null
    {
        return $this->value;
    }

    public function setValue(string|int|bool|null $value): void
    {
        $this->value = $value;
    }

    // function that checks if variable is initialized
    public function isInitialized(): bool
    {
        if ($this->value === null && $this->type === null) {
            // exit program
            echo "Variable {$this->name} is not initialized\n";
            exit(21);
        }

        return true;
    }
}