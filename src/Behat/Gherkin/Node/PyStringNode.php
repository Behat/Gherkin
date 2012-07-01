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
 * PyString Argument Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PyStringNode implements StepArgumentNodeInterface
{
    private $lines = array();

    /**
     * Initializes PyString.
     *
     * @param string $string Initial string
     */
    public function __construct($string = null)
    {
        if (null !== $string) {
            $string = preg_replace("/\r\n|\r/", "\n", $string);
            $this->lines = explode("\n", $string);
        }
    }

    /**
     * Returns new PyString node with replaced outline example row tokens.
     *
     * @param array $tokens
     *
     * @return ExamplePyStringNode
     */
    public function createExampleRowStepArgument(array $tokens)
    {
        return new ExamplePyStringNode($this, $tokens);
    }

    /**
     * Adds a line to the PyString.
     *
     * @param string $line Line of text
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * Sets PyString lines.
     *
     * @param array $lines Array of text lines
     */
    public function setLines(array $lines)
    {
        $this->lines = $lines;
    }

    /**
     * Returns PyString lines.
     *
     * @return array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Returns raw string.
     *
     * @return string
     */
    public function getRaw()
    {
        return implode("\n", $this->lines);
    }

    /**
     * Converts PyString into string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRaw();
    }
}
