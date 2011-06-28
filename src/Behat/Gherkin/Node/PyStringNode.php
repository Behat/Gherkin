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
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PyStringNode
{
    private $lines = array();

    /**
     * Initializes PyString.
     *
     * @param   string  $string         initial string
     */
    public function __construct($string = null)
    {
        if (null !== $string) {
            $string = preg_replace("/\r\n|\r/", "\n", $string);
            $this->lines = explode("\n", $string);
        }
    }

    /**
     * Replaces PyString holders with tokens.
     *
     * @param   array   $tokens     hash (search => replace)
     */
    public function replaceTokens(array $tokens)
    {
        foreach ($tokens as $key => $value) {
            foreach (array_keys($this->lines) as $line) {
                $this->lines[$line] = str_replace('<' . $key . '>', $value, $this->lines[$line], $count);
            }
        }
    }

    /**
     * Adds a line to the PyString.
     *
     * @param   string  $line
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * Returns PyString lines.
     *
     * @return  array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Returns raw string.
     *
     * @return  string
     */
    public function getRaw()
    {
        return implode("\n", $this->lines);
    }

    /**
     * Converts PyString into string.
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->getRaw();
    }
}
