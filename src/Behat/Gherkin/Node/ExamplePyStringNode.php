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
 * PyString Argument for outline examples row Gherkin AST node.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExamplePyStringNode extends PyStringNode
{
    private $cleanLines = array();

    /**
     * Initializes PyString.
     *
     * @param PyStringNode $simpleString String from which this example string should be created
     * @param array        $tokens       Replacement tokens values
     */
    public function __construct(PyStringNode $simpleString, array $tokens)
    {
        $this->cleanLines = $lines = $simpleString->getLines();
        foreach ($tokens as $key => $value) {
            foreach (array_keys($lines) as $line) {
                $lines[$line] = str_replace('<'.$key.'>', $value, $lines[$line]);
            }
        }

        $this->setLines($lines);
    }

    /**
     * Returns not replaced with tokens string lines.
     *
     * @return array
     */
    public function getCleanLines()
    {
        return $this->cleanLines;
    }
}
