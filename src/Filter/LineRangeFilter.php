<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filters scenarios by definition line number range.
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 */
class LineRangeFilter implements FilterInterface
{
    /**
     * @var int
     */
    protected $filterMinLine;
    /**
     * @var int
     */
    protected $filterMaxLine;

    /**
     * Initializes filter.
     *
     * @param int|numeric-string $filterMinLine Minimum line of a scenario to filter on
     * @param int|numeric-string|'*' $filterMaxLine Maximum line of a scenario to filter on
     */
    public function __construct($filterMinLine, $filterMaxLine)
    {
        $this->filterMinLine = (int) $filterMinLine;
        $this->filterMaxLine = $filterMaxLine === '*' ? PHP_INT_MAX : (int) $filterMaxLine;
    }

    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return bool
     */
    public function isFeatureMatch(FeatureNode $feature)
    {
        return $this->filterMinLine <= $feature->getLine()
            && $this->filterMaxLine >= $feature->getLine();
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return bool
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        if ($this->filterMinLine <= $scenario->getLine() && $this->filterMaxLine >= $scenario->getLine()) {
            return true;
        }

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            foreach ($scenario->getExampleTable()->getLines() as $line) {
                if ($this->filterMinLine <= $line && $this->filterMaxLine >= $line) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        $scenarios = [];
        foreach ($feature->getScenarios() as $scenario) {
            if (!$this->isScenarioMatch($scenario)) {
                continue;
            }

            if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
                // first accumulate examples and then create scenario
                $exampleTableNodes = [];

                foreach ($scenario->getExampleTables() as $exampleTable) {
                    $table = $exampleTable->getTable();
                    $lines = array_keys($table);

                    $filteredTable = [$lines[0] => $table[$lines[0]]];
                    unset($table[$lines[0]]);

                    foreach ($table as $line => $row) {
                        if ($this->filterMinLine <= $line && $this->filterMaxLine >= $line) {
                            $filteredTable[$line] = $row;
                        }
                    }

                    if (count($filteredTable) > 1) {
                        $exampleTableNodes[] = new ExampleTableNode($filteredTable, $exampleTable->getKeyword(), $exampleTable->getTags());
                    }
                }

                $scenario = $scenario->withTables($exampleTableNodes);
            }

            $scenarios[] = $scenario;
        }

        return $feature->withScenarios($scenarios);
    }
}
