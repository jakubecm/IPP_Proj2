<?php

namespace IPP\Student;

class Stack{

    /**
     * @var mixed[] Stack elements
     */
    private array $stack;

    public function __construct(){
        $this->stack = [];
    }

    /**
     * Pushes an item onto the stack.
     *
     * @param mixed $item The item to push.
     * @return void
     */
    public function push($item){
        array_push($this->stack, $item);
    }

    /**
     * Pops an item from the stack.
     *
     * @return mixed The popped item.
     * @throws \Exception If the stack is empty.
     */
    public function pop(){
        if($this->isEmpty()){
            throw new \Exception("Stack is empty");
        }
        return array_pop($this->stack);
    }

    /**
     * Checks if the stack is empty.
     *
     * @return bool True if the stack is empty, false otherwise.
     */
    public function isEmpty(){
        return empty($this->stack);
    }

    /**
     * Returns the item at the top of the stack without removing it.
     *
     * @return mixed The item at the top of the stack.
     */
    public function top(){
        return end($this->stack);
    }

}