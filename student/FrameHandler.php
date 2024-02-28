<?php

namespace IPP\Student;

class FrameHandler{

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
                    // exit program
                    exit(55);
                }
                $this->localFrame->top()->addVariable($var_name);
                break;
            case 'TF':
                // check if temporary frame exists
                if ($this->temporaryFrame === null) {
                    // exit program
                    exit(55);
                }
                $this->temporaryFrame->addVariable($var_name);
                break;
            default:
                // exit program
                exit(32);
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
                    // exit program
                    exit(55);
                }
                return $this->localFrame->top()->getVariable($var_name);
            case 'TF':
                // check if temporary frame exists
                if ($this->temporaryFrame === null) {
                    // exit program
                    exit(55);
                }
                return $this->temporaryFrame->getVariable($var_name);
            default:
                // exit program
                exit(32);
        }
    }
}