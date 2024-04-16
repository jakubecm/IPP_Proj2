<?php

namespace IPP\Student;

use IPP\Student\Exceptions\ValueException;

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

    /**
     * Name getter
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Type getter
     * @return string|null
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * Value getter
     * @return string|int|bool|null
     */
    public function getValue(): string|int|bool|null
    {
        return $this->value;
    }

    /**
     * Name setter
     * @param string|null $name
     */
    public function setName(string|null $name): void
    {
        $this->name = $name;
    }

    /**
     * Value setter
     * @param string|int|bool|null $value
     */
    public function setValue(string|int|bool|null $value): void
    {
        $this->value = $value;
    }

    /**
     * Type setter
     * @param string|null $type
     */
    public function setType(string|null $type): void
    {
        $this->type = $type;
    }

    /**
     * Checks if the variable is initialized
     * @return bool
     * @throws ValueException if the variable is not initialized
     */
    public function isInitialized(): bool
    {
        if ($this->value === null && $this->type === null) {

            throw new ValueException("Variable {$this->name} is not initialized");
        }

        return true;
    }
}