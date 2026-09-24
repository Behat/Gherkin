<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filters scenarios by definition line number range.
 *
 * @author Fabian Kiss <headrevision@gmail.com>
 *
 * @phpstan-ignore class.extendsDeprecatedClass (Needs to keep the existing interface for BC)
 */
class LineRangeFilter extends SimpleFilter
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
    public function __construct(int|string $filterMinLine, int|string $filterMaxLine)
    {
        $this->filterMinLine = (int) $filterMinLine;
        $this->filterMaxLine = $filterMaxLine === '*' ? PHP_INT_MAX : (int) $filterMaxLine;

        // Always filter the individual children, don't check the feature itself
        parent::__construct(skipFilteringChildrenIfFeatureMatches: false);
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
        return $this->isInLineRange($feature->getLine());
    }

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return bool
     *
     * @deprecated see FilterInterface for further information
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        if ($this->isInLineRange($scenario->getLine())) {
            return true;
        }

        if ($scenario instanceof OutlineNode && $scenario->hasExamples()) {
            foreach ($scenario->getExampleTables() as $table) {
                foreach ($table->getLines() as $line) {
                    if ($this->isInLineRange($line)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function filterScenario(FeatureNode $feature, ScenarioInterface $scenario): ScenarioInterface|false
    {
        /* @phpstan-ignore method.deprecated (Needs to keep the existing control flow for BC with classes that extend this) */
        if (!$this->isScenarioMatch($scenario)) {
            return false;
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
                    if ($this->isInLineRange($line)) {
                        $filteredTable[$line] = $row;
                    }
                }

                if (count($filteredTable) > 1) {
                    $exampleTableNodes[] = $exampleTable->withTable($filteredTable);
                }
            }

            return $scenario->withTables($exampleTableNodes);
        }

        return $scenario;
    }

    private function isInLineRange(int $line): bool
    {
        return $this->filterMinLine <= $line && $this->filterMaxLine >= $line;
    }
}
