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
 * Scenario Outline Gherkin AST node.
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineNode extends ScenarioNode
{
    private $examples;

    /**
     * Set outline examples table.
     *
     * @param   TableNode   $examples
     */
    public function setExamples(TableNode $examples)
    {
        $this->examples = $examples;
    }

    /**
     * Check if outline has examples.
     *
     * @return  boolean
     */
    public function hasExamples()
    {
        return null !== $this->examples;
    }

    /**
     * Return examples table.
     *
     * @return  TableNode
     */
    public function getExamples()
    {
        return $this->examples;
    }
}
