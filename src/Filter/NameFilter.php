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
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filters scenarios by feature/scenario name.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NameFilter extends SimpleFilter
{
    /**
     * @var string
     */
    protected $filterString;

    /**
     * Initializes filter.
     *
     * @param string $filterString Name filter string
     */
    public function __construct($filterString)
    {
        $this->filterString = trim($filterString);
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
        if ($feature->getTitle() === null) {
            return false;
        }

        if ($this->filterString[0] === '/') {
            return (bool) preg_match($this->filterString, $feature->getTitle());
        }

        return str_contains($feature->getTitle(), $this->filterString);
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
        if ($scenario->getTitle() === null) {
            return false;
        }

        if ($this->filterString[0] === '/' && preg_match($this->filterString, $scenario->getTitle())) {
            return true;
        }

        if (str_contains($scenario->getTitle(), $this->filterString)) {
            return true;
        }

        return false;
    }
}
