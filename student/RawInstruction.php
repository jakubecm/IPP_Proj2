<?php

namespace IPP\Student;

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

    public function prepareArgsForExecution(): array
    {
        $preparedArgs = [];
        foreach ($this->arguments as $arg) {

            if ($arg->type === 'var') {
                echo "Finding variable: {$arg->value}\n";
                $preparedArgs[] = $this->interpreter->frameHandler->findVariable($arg->value);
            } else {
                echo "Preparing argument: {$arg->value}\n";
                $preparedArgs[] = new Variable(null, $arg->type, $arg->value);
            }
        }
        return $preparedArgs;
    }

    public function getOpCode(): string
    {
        return $this->opCode;
    }

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
        $variables[0]->setValue($variables[1]->getValue());
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
            // exit program
            echo "Call stack is empty\n";
            exit(56);
        }
        
        $next_ip = $this->interpreter->callStack->pop();
        $this->interpreter->setInstructionPointer($next_ip);
    }
}
class WRITE extends RawInstruction
{
    public function execute(): void
    {
        $argument = $this->arguments[0];
        echo $argument->value;
    }
}
