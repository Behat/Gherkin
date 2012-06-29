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
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutlineNode extends ScenarioNode
{
    private $examples;

    /**
     * Sets outline examples table.
     *
     * @param TableNode $examples Examples table
     *
     * @throws \LogicException if feature is frozen
     */
    public function setExamples(TableNode $examples)
    {
        if ($this->isFrozen()) {
            throw new \LogicException('Impossible to change outline examples in frozen feature.');
        }

        $this->examples = $examples;
    }

    /**
     * Checks if outline has examples.
     *
     * @return Boolean
     */
    public function hasExamples()
    {
        return null !== $this->examples;
    }

    /**
     * Returns examples table.
     *
     * @return TableNode
     */
    public function getExamples()
    {
        return $this->examples;
    }
}
