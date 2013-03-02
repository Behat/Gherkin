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
 * Filters scenarios by definition line number range.
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class LineRangeFilter implements FilterInterface
{
    protected $filterMinLine;
    protected $filterMaxLine;

    /**
     * Initializes filter.
     *
     * @param string $filterMinLine Minimum line of a scenario to filter on
     * @param string $filterMaxLine Maximum line of a scenario to filter on
     */
    public function __construct($filterMinLine, $filterMaxLine)
    {
        $this->filterMinLine = intval($filterMinLine);
        if ($filterMaxLine == '*') {
            $this->filterMaxLine = PHP_INT_MAX;
        } else {
            $this->filterMaxLine = intval($filterMaxLine);
        }
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
        return $this->filterMinLine <= $feature->getLine()
            && $this->filterMaxLine >= $feature->getLine()
        ;
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
        if ($this->filterMinLine <= $scenario->getLine()
         && $this->filterMaxLine >= $scenario->getLine()) {
            return true;
        }

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            foreach ($scenario->getExamples()->getRowLines() as $line) {
                if ($line >= $this->filterMinLine && $line <= $this->filterMaxLine) {
                    return true;
                }
            }
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

                $scenario->getExamples()->setRows(array());
                $scenario->getExamples()->addRow($rows[$lines[0]], $lines[0]);
                unset($rows[$lines[0]]);

                foreach ($rows as $line => $row) {
                    if ($this->filterMinLine <= $line && $this->filterMaxLine >= $line) {
                        $scenario->getExamples()->addRow($row, $line);
                    }
                }
            }
        }

        $feature->setScenarios($scenarios);
    }
}
