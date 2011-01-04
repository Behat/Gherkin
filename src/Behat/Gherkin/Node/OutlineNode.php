<?php

namespace Behat\Gherkin\Node;

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
