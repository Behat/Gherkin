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
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class AbstractNode
{
    private $line;
    private $keyword;

    /**
     * Initializes node.
     *
     * @param integer $line Line number
     */
    public function __construct($line = 0)
    {
        $this->line = $line;
    }

    /**
     * Accepts specific visitor & visits current node.
     *
     * @param NodeVisitorInterface $visitor Node visitor
     *
     * @return mixed
     */
    public function accept(NodeVisitorInterface $visitor)
    {
        return $visitor->visit($this);
    }

    /**
     * Returns node line number.
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Sets current node definition keyword.
     *
     * @param string $keyword Keyword
     *
     * @throws \LogicException if feature is frozen
     */
    public function setKeyword($keyword)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change frozen node keyword.');
        }

        $this->keyword = $keyword;
    }

    /**
     * Returns current node definition keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Checks whether node has been frozen.
     *
     * @return Boolean
     */
    abstract public function isFrozen();
}
