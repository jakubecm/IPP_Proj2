<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\InternalErrorException;
use IPP\Core\Exception\NotImplementedException;
use IPP\Student\Exceptions\SemanticErrorException;

class Interpreter extends AbstractInterpreter
{
    public \DOMDocument $xmlFile;

    /** @var array<RawInstruction> */
    private array $instructions = [];

    /** @var array<string> */
    public array $labelDefinitions = [];

    private int $instructionPointer;
    public FrameHandler $frameHandler;
    public Stack $callStack;
    public Stack $dataStack;


    public function execute(): int
    {
        $this->loadAndPrepareInstructions();
        $this->frameHandler = new FrameHandler();
        $this->callStack = new Stack();
        $this->dataStack = new Stack();

        while ($this->instructionPointer < count($this->instructions)) {

            $currentInstructionPointer = $this->instructionPointer;
            $this->instructions[$currentInstructionPointer]->execute();

            // Check if the current instruction was a JUMP and successfully changed the pointer
            if ($currentInstructionPointer == $this->instructionPointer) {
                // If not, increment to proceed to the next instruction
                $this->instructionPointer++;
            }
        }

        return 0;
    }

    /**
     * Loads and prepares instructions from the XML file.
     */
    private function loadAndPrepareInstructions(): void
    {
        $this->xmlFile = $this->source->getDOMDocument();
        XMLValidator::validateXMLStructure($this->xmlFile, $this);
        $this->instructionPointer = 0;
        $this->instructions = $this->parseInstructions($this->xmlFile);
        $this->sortInstructions();
        $this->mapLabelsToSortedInstructions();
    }

    /**
     * Processes XML file and returns an array of instruction objects.
     *
     * @param \DOMDocument $xmlFile The XML file to process.
     * @return RawInstruction[] The parsed instructions as an array of RawInstruction objects.
     */
    private function parseInstructions(\DOMDocument $xmlFile): array
    {
        $parsedInstructions = [];
        $xmlInstructions = $xmlFile->getElementsByTagName('instruction');
        foreach ($xmlInstructions as $instruction) {
            $opcode = $instruction->getAttribute('opcode');
            $instructionObj = InstructionFactory::createInstruction($this, $instruction, $opcode);
            $parsedInstructions[] = $instructionObj;
        }
        return $parsedInstructions;
    }

    /**
     * Sorts the instructions by their order attribute.
     */
    private function sortInstructions(): void
    {
        usort($this->instructions, function ($a, $b) {
            return $a->order - $b->order;
        });
    }

    /**
     * Returns the current instruction pointer.
     *
     * @return int The current instruction pointer.
     */
    public function getInstructionPointer(): int
    {
        return $this->instructionPointer;
    }

    /**
     * Sets the instruction pointer.
     *
     * @param int $instructionPointer The new instruction pointer.
     */
    public function setInstructionPointer(int $instructionPointer): void
    {
        $this->instructionPointer = $instructionPointer;
    }


    /**
     * Maps labels to their corresponding instruction index.
     * @throws SemanticErrorException If a label is already defined.
     */
    private function mapLabelsToSortedInstructions(): void
    {
        $this->labelDefinitions = [];

        foreach ($this->instructions as $index => $instruction) {
            if ($instruction->getOpCode() === 'LABEL') {
                $labelName = $instruction->getArguments()[0]->value;
                if (isset($this->labelDefinitions[$labelName])) {

                    throw new SemanticErrorException("Label '$labelName' already defined.");
                }
                $this->labelDefinitions[$labelName] = $index;
            }
        }
    }

    /**
     * Jumps to the instruction with the specified label.
     * @param string $label The label to jump to.
     * @throws SemanticErrorException If the label is not defined.
     */
    public function jmp_label(string $label): void
    {
        if (isset($this->labelDefinitions[$label])) {
            $this->instructionPointer = intval($this->labelDefinitions[$label]);
        } else {

            throw new SemanticErrorException("Label '$label' not defined.");
        }
    }

    /**
     * Reads input from the input stream.
     * @param string $type The type of the input.
     * @return string|int|bool|null The input value.
     * @throws InternalErrorException If the input type is invalid.
     */
    public function readInput(string $type): string|int|bool|null
    {
        if ($type === 'int') {
            return $this->input->readInt();
        } elseif ($type === 'bool') {
            return $this->input->readBool();
        } elseif ($type === 'string') {
            return $this->input->readString();
        } else {
            throw new InternalErrorException("Invalid input type '$type'.");
        }
    }

    /**
     * Writes output to the output stream.
     * @param string|null $type The type of the output.
     * @param string|int|bool|null $value The output value.
     * @throws InternalErrorException If the output type is invalid.
     */
    public function writeOutput(string|null $type, string|int|bool|null $value): void
    {
        if ($type === 'int') {
            $this->stdout->writeInt($value);
        } elseif ($type === 'bool') {
            $this->stdout->writeBool($value);
        } elseif ($type === 'string') {
            $this->stdout->writeString($value);
        } elseif ($type == null || $type === 'nil') {
            $this->stdout->writeString('');
        } else {
            throw new InternalErrorException("Invalid output type '$type'.");
        }
    }

    /**
     * Writes an error message to the error stream.
     * @param string $message The error message.
     */
    public function writeError(string $message): void
    {
        $this->stderr->writeString($message);
    }
}
