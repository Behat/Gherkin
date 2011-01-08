<?php

namespace Behat\Gherkin\Node;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Abstract Gherkin AST node.
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
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
     * Accept specific visitor & visit current node.
     *
     * @param   NodeVisitorInterface    $visitor
     * 
     * @return  mixed
     */
    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visit($this);
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
