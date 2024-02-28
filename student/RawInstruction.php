<?php

namespace IPP\Student;

abstract class RawInstruction
{

    protected Interpreter $interpreter;
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
                $preparedArgs[] = $this->interpreter->frameHandler->findVariable($arg->value);
            } else {
                $preparedArgs[] = new Variable(null, $arg->type, $arg->value);
            }
        }
        return $preparedArgs;
    }
}

class DEFVAR extends RawInstruction
{
    public function execute(): void
    {
        // Logic to define a variable
        echo "Defining variable: {$this->arguments[0]->value}\n";
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
