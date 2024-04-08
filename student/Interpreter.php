<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

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

    private function sortInstructions(): void
    {
        usort($this->instructions, function ($a, $b) {
            return $a->order - $b->order;
        });
    }

    public function getInstructionPointer(): int
    {
        return $this->instructionPointer;
    }

    public function setInstructionPointer(int $instructionPointer): void
    {
        $this->instructionPointer = $instructionPointer;
    }

    private function mapLabelsToSortedInstructions(): void
    {
        $this->labelDefinitions = [];

        foreach ($this->instructions as $index => $instruction) {
            if ($instruction->getOpCode() === 'LABEL') {
                $labelName = $instruction->getArguments()[0]->value;
                if (isset($this->labelDefinitions[$labelName])) {
                    $this->writeError("Error: Label '$labelName' already defined.\n");
                    exit(52);
                }
                $this->labelDefinitions[$labelName] = $index;
            }
        }
    }

    public function jmp_label(string $label): void
    {
        if (isset($this->labelDefinitions[$label])) {
            $this->instructionPointer = intval($this->labelDefinitions[$label]);
        } else {
            $this->writeError("Error: Label '$label' not defined.\n");
            exit(52);
        }
    }

    public function readInput(string $type): string|int|bool|null
    {
        if ($type === 'int') {
            return $this->input->readInt();
        } elseif ($type === 'bool') {
            return $this->input->readBool();
        } elseif ($type === 'string') {
            return $this->input->readString();
        } else {
            throw new NotImplementedException;
        }
    }

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
            throw new NotImplementedException;
        }
    }

    public function writeError(string $message): void
    {
        $this->stderr->writeString($message);
    }
}
