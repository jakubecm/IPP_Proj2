<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

class Interpreter extends AbstractInterpreter
{
    public \DOMDocument $xmlFile;

    /** @var array<RawInstruction> */
    private array $instructions = [];

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

        $previousInstructionPointer = $this->instructionPointer;

        while ($this->instructionPointer < count($this->instructions)) {

            $this->instructions[$this->instructionPointer]->execute();

            if ($previousInstructionPointer == $this->instructionPointer) {
                $this->instructionPointer++;
            } 
            $previousInstructionPointer = $this->instructionPointer;
            
        }
        
        return 0;


        //$val = $this->input->readString();
        // $this->stderr->writeString("stderr");
        //throw new NotImplementedException;
    }

    private function loadAndPrepareInstructions(): void
    {
        $this->xmlFile = $this->source->getDOMDocument();
        $this->instructionPointer = 0;
        $this->instructions = $this->parseInstructions($this->xmlFile);
        $this->sortInstructions();
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

    public function jmp_label(string $label): void
    {
        for ($i = 0; $i < count($this->instructions); $i++) {
            if ($this->instructions[$i]->getOpCode() === 'LABEL' && $this->instructions[$i]->getArguments()[0]->value === $label) {
                $this->instructionPointer = $i;
                return;
            }
        }

        // label not found
        echo "Label $label not found\n";
        exit(52);
    }

    public function readInput(string $type): string|int|bool
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
        } elseif ($type == null) {
            $this->stdout->writeString('');
        } else {
            throw new NotImplementedException;
        }
    }
}
