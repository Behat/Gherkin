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
    private $ltrimCount;
    private $lines = array();

    /**
     * Initialize PyString.
     *
     * @param   string  $string         initial string
     * @param   integer $ltrimCount     left-trim count
     */
    public function __construct($string = null, $ltrimCount = 0)
    {
        $this->ltrimCount = $ltrimCount;

        if (null !== $string) {
            $string = preg_replace("/\r\n|\r/", "\n", $string);

            foreach (explode("\n", $string) as $line) {
                $this->addLine($line);
            }
        }
    }

    /**
     * Replace PyString holders with tokens.
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
     * Add line to the PyString.
     *
     * @param   string  $line
     */
    public function addLine($line)
    {
        if ($this->ltrimCount >= 1) {
            $line = preg_replace('/^\s{1,' . $this->ltrimCount . '}/', '', $line);
        }

        $this->lines[] = $line;
    }

    /**
     * Return PyString lines.
     *
     * @return  array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Convert PyString lines array into string.
     *
     * @return  string
     */
    public function __toString()
    {
        return implode("\n", $this->lines);
    }
}
