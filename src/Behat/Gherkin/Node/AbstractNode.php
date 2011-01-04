<?php

namespace Behat\Gherkin\Node;

abstract class AbstractNode
{
    private $line;

    /**
     * Initialize node.
     *
     * @param   integer $line   line number
     */
    public function __construct($line = 0)
    {
        $this->line = $line;
    }

    /**
     * Return definition line number.
     *
     * @return  integer
     */
    public function getLine()
    {
        return $this->line;
    }
}
