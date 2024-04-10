<?php

namespace IPP\Student;

use IPP\Student\Exceptions\FrameAccessException;
use IPP\Student\Exceptions\SourceStructureException;

class FrameHandler
{

    private Frame $globalFrame;
    private Frame|null $temporaryFrame;
    private Stack $localFrame;

    public function __construct()
    {
        $this->globalFrame = new Frame();
        $this->temporaryFrame = null;
        $this->localFrame = new Stack();
    }

    public function insertVariable(string $variable): void
    {
        // split the variable string on symbol @
        $variable = explode('@', $variable);
        $frame = $variable[0];
        $var_name = $variable[1];


        switch ($frame) {
            case 'GF':
                $this->globalFrame->addVariable($var_name);
                break;
            case 'LF':
                if ($this->localFrame->isEmpty()) {

                    throw new FrameAccessException("Local frame is empty");
                }
                $this->localFrame->top()->addVariable($var_name);
                break;
            case 'TF':
                // check if temporary frame exists
                if ($this->temporaryFrame === null) {

                    throw new FrameAccessException("Temporary frame does not exist");
                }
                $this->temporaryFrame->addVariable($var_name);
                break;
            default:

                throw new SourceStructureException("Invalid frame");
        }
    }

    public function findVariable(string $variable): Variable
    {
        // split the variable string on symbol @
        $variable = explode('@', $variable);
        $frame = $variable[0];
        $var_name = $variable[1];

        switch ($frame) {
            case 'GF':
                return $this->globalFrame->getVariable($var_name);
            case 'LF':
                if ($this->localFrame->isEmpty()) {

                    throw new FrameAccessException("Local frame is empty");
                }
                return $this->localFrame->top()->getVariable($var_name);
            case 'TF':
                // check if temporary frame exists
                if ($this->temporaryFrame === null) {

                    throw new FrameAccessException("Temporary frame does not exist");
                }
                return $this->temporaryFrame->getVariable($var_name);
            default:

                throw new SourceStructureException("Invalid frame");
        }
    }

    public function createTemporaryFrame(): void
    {
        $this->temporaryFrame = new Frame();
    }

    public function pushTemporaryFrame(): void
    {
        if ($this->temporaryFrame === null) {
            // exit program
            //echo "Temporary frame does not exist\n";
            throw new FrameAccessException("Temporary frame does not exist");
        }
        $this->localFrame->push($this->temporaryFrame);
        $this->temporaryFrame = null;
    }

    public function popLocalFrame(): void
    {
        if ($this->localFrame->isEmpty()) {

            throw new FrameAccessException("Local framestack is empty");
        }
        $this->temporaryFrame = $this->localFrame->pop();
    }
}
