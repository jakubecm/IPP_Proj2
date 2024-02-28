<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;

class Interpreter extends AbstractInterpreter
{
    public \DOMDocument $xmlFile;

    /** @var array<RawInstruction> */
    protected array $instructions = [];

    protected int $instructionPointer;
    public FrameHandler $frameHandler;
    

    public function execute(): int
    {
        $this->loadAndPrepareInstructions();
        $this->frameHandler = new FrameHandler();

        while ($this->instructionPointer < count($this->instructions)) {
            $this->instructions[$this->instructionPointer]->execute();
            $this->instructionPointer++;
        }
        return 0;


        // $val = $this->input->readString();
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
}
