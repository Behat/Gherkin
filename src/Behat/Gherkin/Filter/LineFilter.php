<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\OutlineNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters scenarios by definition line number.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class LineFilter implements FilterInterface
{
    protected $filterLine;

    /**
     * Initializes filter.
     *
     * @param string $filterLine Line of the scenario to filter on
     */
    public function __construct($filterLine)
    {
        $this->filterLine = intval($filterLine);
    }

    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return Boolean
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return $this->filterLine === $feature->getLine();
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioNode $scenario Scenario or Outline node instance
     *
     * @return Boolean
     */
    public function isScenarioMatch(ScenarioNode $scenario)
    {
        if ($this->filterLine === $scenario->getLine()) {
            return true;
        }

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            return $this->filterLine === $scenario->getLine()
                || in_array($this->filterLine, $scenario->getExamples()->getRowLines());
        }

        return false;
    }

    /**
     * Filters feature according to the filter.
     *
     * @param FeatureNode $feature
     */
    public function filterFeature(FeatureNode $feature)
    {
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $i => $scenario) {
            if (!$this->isScenarioMatch($scenario)) {
                unset($scenarios[$i]);
                continue;
            }

            if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
                $lines = $scenario->getExamples()->getRowLines();
                $rows  = $scenario->getExamples()->getNumeratedRows();

                if (current($lines) <= $this->filterLine && end($lines) >= $this->filterLine) {
                    $scenario->getExamples()->setRows(array());
                    $scenario->getExamples()->addRow($rows[$lines[0]], $lines[0]);

                    if ($lines[0] !== $this->filterLine) {
                        $scenario->getExamples()->addRow($rows[$this->filterLine], $this->filterLine);
                    }
                }
            }
        }

        $feature->setScenarios($scenarios);
    }
}
