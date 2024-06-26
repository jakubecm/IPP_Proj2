<?php

namespace IPP\Student;

use IPP\Student\Exceptions\OperandTypeException;
use IPP\Student\Exceptions\OperandValueException;
use IPP\Student\Exceptions\SourceStructureException;
use IPP\Student\Exceptions\StringOperationException;
use IPP\Student\Exceptions\ValueException;

abstract class RawInstruction
{

    public Interpreter $interpreter;
    public int $order;
    protected string $opCode;

    /** @var array<Arg> */
    protected array $arguments;

    public function __construct(Interpreter $interpreter, \DOMElement $xmlElement)
    {
        $this->interpreter = $interpreter;
        $this->order = intval($xmlElement->getAttribute('order'));
        $this->opCode = $xmlElement->getAttribute('opcode');
        $this->arguments = $this->processArgs($xmlElement);
    }

    abstract public function execute(): void;


    /**
     * Processes XML element arguments and returns an array of Arg objects.
     *
     * @param \DOMElement $xmlElement The XML element to process.
     * @return Arg[] The processed arguments as an array of Arg objects.
     */
    public function processArgs(\DOMElement $xmlElement): array
    {
        $processedArgs = [];
        for ($i = 1; $i <= 3; $i++) {
            $arg = $xmlElement->getElementsByTagName("arg$i");
            if ($arg->length > 0) {
                $arg = $arg->item(0);
                if ($arg instanceof \DOMElement) {
                    $type = $arg->getAttribute('type');
                    $value = trim($arg->nodeValue);
                    $processedArgs[] = new Arg($this->interpreter, $type, $value);
                }
            }
        }
        return $processedArgs;
    }

    /**
     * Prepares the arguments and returns them as Variables for easier manipulation.
     *
     * @return Variable[] The prepared args as a Var array.
     */
    public function prepareArgsForExecution(): array
    {
        $preparedArgs = [];
        foreach ($this->arguments as $arg) {

            if ($arg->type === 'var') {
                $preparedArgs[] = $this->interpreter->frameHandler->findVariable($arg->value);
            } else {
                $preparedArgs[] = new Variable(null, $arg->type, $arg->value);
            }
        }
        return $preparedArgs;
    }

    /**
     * Opcode getter.
     *
     * @return string The opcode.
     */
    public function getOpCode(): string
    {
        return $this->opCode;
    }

    /**
     * Arguments getter.
     *
     * @return Arg[] The arguments as an array.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}


/**
 * MOVE <var> <symb>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb> - constant or variable (var, int, bool, string, nil)
 * 
 * Copies value of <symb> to <var>
 */
class MOVE extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for MOVE instruction");
        }
        $variables[0]->setValue($variables[1]->getValue());
        $variables[0]->setType($variables[1]->getType());
    }
}

/**
 * CREATEFRAME
 * 
 * Creates a new temporary frame and if it already exists, it is rewritten.
 */
class CREATEFRAME extends RawInstruction
{
    public function execute(): void
    {
        $this->interpreter->frameHandler->createTemporaryFrame();
    }
}

/**
 * PUSHFRAME
 * 
 * Pushes the temporary frame to the stack of frames.
 * The frame becomes local and becomes the top frame on the stack.
 * Temporary frame will be undefined after this instruction and before using it again, 
 * it has to be created with the instruction CREATEFRAME.
 */
class PUSHFRAME extends RawInstruction
{
    public function execute(): void
    {
        $this->interpreter->frameHandler->pushTemporaryFrame();
    }
}

/**
 * POPFRAME
 * 
 * Pops the top frame from the stack of local frames.
 * The frame becomes temporary and the previous frame becomes the top local frame.
 * If the stack of frames is empty, it leads to error 55.
 */
class POPFRAME extends RawInstruction
{
    public function execute(): void
    {
        $this->interpreter->frameHandler->popLocalFrame();
    }
}

/**
 * DEFVAR <var>
 * <var> - variable (GF@var, LF@var, TF@var)
 * 
 * Defines a new variable according to <var>.
 * The variable is not initialized and its value and type are undefined.
 * If the variable already exists in the frame, it leads to error 52.
 */
class DEFVAR extends RawInstruction
{
    public function execute(): void
    {
        if (count($this->arguments) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for DEFVAR instruction");
        }
        $this->interpreter->frameHandler->insertVariable($this->arguments[0]->value);
    }
}

/**
 * CALL <label>
 * <label> - label
 * 
 * Saves incremented order of the next instruction to the call stack and jumps to the label.
 * (preparation of memory frame must be done before the jump by other instructions)
 */
class CALL extends RawInstruction
{
    public function execute(): void
    {
        $incremented_instruction_ptr = $this->interpreter->getInstructionPointer() + 1;
        $this->interpreter->callStack->push($incremented_instruction_ptr);
        $this->interpreter->jmp_label($this->arguments[0]->value);
    }
}

/**
 * RETURN
 * 
 * Pops the order of the next instruction from the call stack and jumps to it.
 * Cleanup of the memory frame must be done by other instructions.
 * If the call stack is empty, it leads to error 56.
 */
class RETURN_I extends RawInstruction
{
    public function execute(): void
    {
        if ($this->interpreter->callStack->isEmpty()) {

            throw new ValueException("Call stack is empty");
        }

        $next_ip = $this->interpreter->callStack->pop();
        $this->interpreter->setInstructionPointer($next_ip);
    }
}

/**
 * PUSHS <symb>
 * <symb> - constant or variable (var, int, bool, string, nil)
 * 
 * Pushes the value of <symb> to the stack.
 */
class PUSHS extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for PUSHS instruction");
        }
        $item = new Variable($variables[0]->getName(), $variables[0]->getType(), $variables[0]->getValue());
        $this->interpreter->dataStack->push($item);
    }
}

/**
 * POPS <var>
 * <var> - variable (GF@var, LF@var, TF@var)
 * 
 * Pops the value from the stack and stores it to <var>.
 * If the stack is empty, it leads to error 56.
 */
class POPS extends RawInstruction
{
    public function execute(): void
    {
        if ($this->interpreter->dataStack->isEmpty()) {

            throw new ValueException("Data stack is empty");
        }
        $variable = $this->prepareArgsForExecution();

        if (count($variable) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for POPS instruction");
        }
        $value_to_set = $this->interpreter->dataStack->pop();
        $variable[0]->setValue($value_to_set->getValue());
        $variable[0]->setType($value_to_set->getType());
    }
}

/**
 * ADD <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int), type always must be int
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Adds values of <symb1> and <symb2> and stores the result to <var>.
 */
class ADD extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for ADD instruction");
        }

        if ($variables[1]->getType() != 'int' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("ADD: symb1 and symb2 must be of type int, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() + $variables[2]->getValue());
        $variables[0]->setType('int');
    }
}

/**
 * SUB <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int), type always must be int
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Subtracts value of <symb2> from <symb1> and stores the result to <var>.
 */
class SUB extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for SUB instruction");
        }

        if ($variables[1]->getType() != 'int' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("SUB: symb1 and symb2 must be of type int, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() - $variables[2]->getValue());
        $variables[0]->setType('int');
    }
}

/**
 * MUL <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int), type always must be int
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Multiplies values of <symb1> and <symb2> and stores the result to <var>.
 */
class MUL extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for MUL instruction");
        }

        if ($variables[1]->getType() != 'int' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("MUL: symb1 and symb2 must be of type int, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() * $variables[2]->getValue());
        $variables[0]->setType('int');
    }
}

/**
 * IDIV <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int), type always must be int
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Divides value of <symb1> by <symb2> and stores the result to <var>.
 * Division by zero leads to error 57.
 */
class IDIV extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for IDIV instruction");
        }

        if ($variables[1]->getType() != 'int' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("IDIV: symb1 and symb2 must be of type int, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        if ($variables[2]->getValue() == 0) {

            throw new OperandValueException("IDIV: division by zero detected");
        }
        $variables[0]->setValue(intval($variables[1]->getValue() / $variables[2]->getValue()));
        $variables[0]->setType('int');
    }
}

/**
 * LT <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int, bool, string)
 * <symb2> - constant or variable (var, int, bool, string)
 * 
 * Compares values of <symb1> and <symb2> and stores the result to <var>.
 * If <symb1> is less than <symb2>, <var> is set to true, otherwise it is set to false.
 * If one of the operands is nil, it leads to error 53.
 */
class LT extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for LT instruction");
        }

        if ($variables[1]->getType() != $variables[2]->getType()) {

            throw new OperandTypeException("LT: symb1 and symb2 must be of the same type, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }

        if ($variables[1]->getType() === 'nil' || $variables[2]->getType() === 'nil') {

            throw new OperandTypeException("LT: operands cannot be nil, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() < $variables[2]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * GT <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int, bool, string)
 * <symb2> - constant or variable (var, int, bool, string)
 * 
 * Compares values of <symb1> and <symb2> and stores the result to <var>.
 * If <symb1> is greater than <symb2>, <var> is set to true, otherwise it is set to false.
 * If one of the operands is nil, it leads to error 53.
 */
class GT extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for GT instruction");
        }

        if ($variables[1]->getType() == 'nil' || $variables[2]->getType() == 'nil') {

            throw new OperandTypeException("GT: operands cannot be nil, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }

        if ($variables[1]->getType() != $variables[2]->getType()) {

            throw new OperandTypeException("GT: symb1 and symb2 must be of the same type, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() > $variables[2]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * EQ <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int, bool, string, nil)
 * <symb2> - constant or variable (var, int, bool, string, nil)
 * 
 * Compares values of <symb1> and <symb2> and stores the result to <var>.
 * If <symb1> is equal to <symb2>, <var> is set to true, otherwise it is set to false.
 */
class EQ extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for EQ instruction");
        }
        if (($variables[1]->getType() != 'nil' && $variables[2]->getType() != 'nil') && ($variables[1]->getType() != $variables[2]->getType())) {

            throw new OperandTypeException("EQ: symb1 and symb2 must be of the same type, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() === $variables[2]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * AND <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, bool), type always must be bool
 * <symb2> - constant or variable (var, bool), type always must be bool
 * 
 * Performs logical AND operation on <symb1> and <symb2> and stores the result to <var>.
 */
class AND_I extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for AND instruction");
        }

        if ($variables[1]->getType() != 'bool' || $variables[2]->getType() != 'bool') {

            throw new OperandTypeException("AND: symb1 and symb2 must be of type bool, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() && $variables[2]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * OR <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, bool), type always must be bool
 * <symb2> - constant or variable (var, bool), type always must be bool
 * 
 * Performs logical OR operation on <symb1> and <symb2> and stores the result to <var>.
 */
class OR_I extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for OR instruction");
        }

        if ($variables[1]->getType() != 'bool' || $variables[2]->getType() != 'bool') {

            throw new OperandTypeException("OR: symb1 and symb2 must be of type bool, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() || $variables[2]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * NOT <var> <symb>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb> - constant or variable (var, bool), type always must be bool
 * 
 * Performs logical NOT operation on <symb> and stores the result to <var>.
 */
class NOT_I extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for NOT instruction");
        }

        if ($variables[1]->getType() != 'bool') {

            throw new OperandTypeException("NOT: symb must be of type bool");
        }
        $variables[0]->setValue(!$variables[1]->getValue());
        $variables[0]->setType('bool');
    }
}

/**
 * INT2CHAR <var> <symb>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb> - constant or variable (var, int)
 * 
 * Numerical value of <symb> is converted to a character and stored to <var> according to Unicode.
 * If the Unicode is invalid, it leads to error 58.
 */
class INT2CHAR extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for INT2CHAR instruction");
        }

        if ($variables[1]->getType() != 'int') {

            throw new OperandTypeException("INT2CHAR: symb must be of type int");
        }
        $unicodeValue = $variables[1]->getValue();
        $char = mb_chr($unicodeValue, 'UTF-8');
        if ($char == false) {

            throw new StringOperationException("INT2CHAR: invalid Unicode value");
        }
        $variables[0]->setValue($char);
        $variables[0]->setType('string');
    }
}

/**
 * STRI2INT <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, string), type always must be string
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Gets the character from the position of <symb2> in <symb1> and stores its Unicode value to <var>.
 * If the position is invalid, it leads to error 58.
 */
class STRI2INT extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for STRI2INT instruction");
        }

        if ($variables[1]->getType() != 'string' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("STRI2INT: symb1 must be of type string and symb2 must be of type int");
        }
        $string = $variables[1]->getValue();
        $position = $variables[2]->getValue();
        if ($position < 0 || $position >= mb_strlen($string, 'UTF-8')) {

            throw new StringOperationException("STRI2INT: invalid position");
        }
        $char = mb_substr($string, $position, 1, 'UTF-8');
        $unicodeValue = mb_ord($char, 'UTF-8');
        $variables[0]->setValue($unicodeValue);
        $variables[0]->setType('int');
    }
}

/**
 * READ <var> <type>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <type> - type (int, bool, string)
 * 
 * Reads the value from the standard input and stores it to <var> according to <type>.
 * Uses ipp-core Reader to read the input.
 * In case of missing or invalid input, nil@nil is stored to <var>.
 */
class READ extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for READ instruction");
        }
        if ($variables[1]->getType() != 'type') {

            throw new SourceStructureException("READ: type must be of type 'type' but got " . $variables[1]->getType());
        }
        $type = $variables[1]->getValue();
        $input = $this->interpreter->readInput($type);

        if ($input == null) {
            $variables[0]->setValue('nil');
            $variables[0]->setType('nil');
            return;
        }
        if ($type === 'int') {
            if (is_numeric($input)) {
                $variables[0]->setValue(intval($input));
                $variables[0]->setType('int');
            } else {
                $variables[0]->setValue('nil');
                $variables[0]->setType('nil');
            }
        } elseif ($type === 'bool') {
            if ($input == true || $input == false) {
                $variables[0]->setValue($input == true);
                $variables[0]->setType('bool');
            } else {
                $variables[0]->setValue('nil');
                $variables[0]->setType('nil');
            }
        } elseif ($type === 'string') {
            $variables[0]->setValue($input);
            $variables[0]->setType('string');
        }
    }
}

/**
 * WRITE <symb>
 * <symb> - constant or variable (var, int, bool, string, nil)
 * 
 * Writes the value of <symb> to the standard output.
 * Uses ipp-core Writer to write the output.
 */
class WRITE extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for WRITE instruction");
        }
        $variables[0]->isInitialized(); // reading from an undefined variable leads to error 56

        $this->interpreter->writeOutput($variables[0]->getType(), $variables[0]->getValue());
    }
}

/**
 * CONCAT <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, string), type always must be string
 * <symb2> - constant or variable (var, string), type always must be string
 * 
 * Concatenates <symb1> and <symb2> and stores the result to <var>.
 */
class CONCAT extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for CONCAT instruction");
        }

        if ($variables[1]->getType() != 'string' || $variables[2]->getType() != 'string') {

            throw new OperandTypeException("CONCAT: symb1 and symb2 must be of type string, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }
        $variables[0]->setValue($variables[1]->getValue() . $variables[2]->getValue());
        $variables[0]->setType('string');
    }
}

/**
 * STRLEN <var> <symb>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb> - constant or variable (var, string), type always must be string
 * 
 * Stores the length of <symb> to <var>.
 */
class STRLEN extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for STRLEN instruction");
        }

        if ($variables[1]->getType() != 'string') {

            throw new OperandTypeException("STRLEN: symb must be of type string");
        }
        $variables[0]->setValue(mb_strlen($variables[1]->getValue(), 'UTF-8'));
        $variables[0]->setType('int');
    }
}

/**
 * GETCHAR <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, string), type always must be string
 * <symb2> - constant or variable (var, int), type always must be int
 * 
 * Gets the character from the position of <symb2> in <symb1> and stores it to <var>.
 * If the position is invalid, it leads to error 58.
 */
class GETCHAR extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for GETCHAR instruction");
        }

        if ($variables[1]->getType() != 'string' || $variables[2]->getType() != 'int') {

            throw new OperandTypeException("GETCHAR: symb1 must be of type string and symb2 must be of type int");
        }
        $string = $variables[1]->getValue();
        $position = $variables[2]->getValue();
        if ($position < 0 || $position >= mb_strlen($string, 'UTF-8')) {

            throw new StringOperationException("GETCHAR: invalid position");
        }
        $char = mb_substr($string, $position, 1, 'UTF-8');
        $variables[0]->setValue($char);
        $variables[0]->setType('string');
    }
}

/**
 * SETCHAR <var> <symb1> <symb2>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb1> - constant or variable (var, int), type always must be int
 * <symb2> - constant or variable (var, string), type always must be string
 * 
 * Sets the character from <symb2> to the position of <symb1> in <var>.
 * If the position is invalid, it leads to error 58.
 */
class SETCHAR extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for SETCHAR instruction");
        }

        if ($variables[1]->getType() != 'int' || $variables[2]->getType() != 'string') {

            throw new OperandTypeException("SETCHAR: symb1 must be of type int and symb2 must be of type string");
        }
        $string = $variables[0]->getValue();
        $position = $variables[1]->getValue();
        $char = $variables[2]->getValue();
        if ($position < 0 || $position >= mb_strlen($string, 'UTF-8')) {

            throw new StringOperationException("SETCHAR: invalid position");
        }
        $string = mb_substr($string, 0, $position, 'UTF-8') . $char . mb_substr($string, $position + 1, null, 'UTF-8');
        $variables[0]->setValue($string);
        $variables[0]->setType('string');
    }
}

/**
 * TYPE <var> <symb>
 * <var> - variable (GF@var, LF@var, TF@var)
 * <symb> - constant or variable (var, int, bool, string, nil)
 * 
 * Stores the type of <symb> to <var>.
 */
class TYPE extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 2) {

            throw new SourceStructureException("Invalid number of arguments for TYPE instruction");
        }

        $symb_type = $variables[1]->getType();

        if ($symb_type == null) {
            $variables[0]->setValue("");
        } else {
            $variables[0]->setValue($symb_type);
        }

        $variables[0]->setType("string");
    }
}

/**
 * LABEL <label>
 * <label> - label
 * 
 * Defines a new label according to <label>.
 * If the label already exists in the frame, it leads to error 52.
 */
class LABEL extends RawInstruction
{
    public function execute(): void
    {
        return;
    }
}

/**
 * JUMP <label>
 * <label> - label
 * 
 * Jumps to the label.
 */
class JUMP extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for JUMP instruction");
        }
        $this->interpreter->jmp_label($variables[0]->getValue());
    }
}

/**
 * JUMPIFEQ <label> <symb1> <symb2>
 * <label> - label
 * <symb1> - constant or variable (var, int, bool, string, nil)
 * <symb2> - constant or variable (var, int, bool, string, nil)
 * 
 * If <symb1> and <symb2> have the same type and value, jumps to the label.
 */
class JUMPIFEQ extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for JUMPIFEQ instruction");
        }

        if ($variables[1]->getType() != $variables[2]->getType()) {
            throw new OperandTypeException("JUMPIFEQ: symb1 and symb2 must be of the same type, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }

        if ($variables[1]->getType() === $variables[2]->getType() && $variables[1]->getValue() === $variables[2]->getValue()) {
            $this->interpreter->jmp_label($variables[0]->getValue());
        }
    }
}

/**
 * JUMPIFNEQ <label> <symb1> <symb2>
 * <label> - label
 * <symb1> - constant or variable (var, int, bool, string, nil)
 * <symb2> - constant or variable (var, int, bool, string, nil)
 * 
 * If <symb1> and <symb2> have same type, but different value, jumps to the label.
 */
class JUMPIFNEQ extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 3) {

            throw new SourceStructureException("Invalid number of arguments for JUMPIFNEQ instruction");
        }

        if ($variables[1]->getType() != $variables[2]->getType()) {

            throw new OperandTypeException("JUMPIFNEQ: symb1 and symb2 must be of the same type, but got " . $variables[1]->getType() . " and " . $variables[2]->getType());
        }

        if ($variables[1]->getType() === $variables[2]->getType() && $variables[1]->getValue() !== $variables[2]->getValue()) {
            $this->interpreter->jmp_label($variables[0]->getValue());
        }
    }
}

/**
 * EXIT <symb>
 * <symb> - constant or variable (var, int), type always must be int in interval <0,9>
 * Invalid exit code leads to error 57.
 * 
 * Ends the program with the exit code.
 */
class EXIT_I extends RawInstruction
{
    public function execute(): void
    {
        $variables = $this->prepareArgsForExecution();

        if (count($variables) !== 1) {

            throw new SourceStructureException("Invalid number of arguments for EXIT instruction");
        }

        if ($variables[0]->getType() !== 'int') {

            throw new OperandTypeException("EXIT: symb must be of type int");
        }

        if ($variables[0]->getValue() < 0 || $variables[0]->getValue() > 9) {

            throw new OperandValueException("EXIT: exit code must be of type int in interval <0,9>");
        }
        exit($variables[0]->getValue());
    }
}

/**
 * DPRINT <symb>
 * <symb> - constant or variable (var, int, bool, string, nil)
 * 
 * Writes the value of <symb> to the standard error output.
 */
class DPRINT extends RawInstruction
{
    public function execute(): void
    {
        return;
    }
}

/**
 * BREAK
 * 
 * Writes the current state of the interpreter to the standard error output.
 */
class BREAK_I extends RawInstruction
{
    public function execute(): void
    {
        return;
    }
}
