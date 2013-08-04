<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\OutlineNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2013 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filters features by their paths.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PathsFilter extends SimpleFilter
{
    protected $filterPaths = array();

    /**
     * Initializes filter.
     *
     * @param array $paths List of approved paths
     */
    public function __construct(array $paths)
    {
        $this->filterPaths = $paths;
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
        foreach ($this->filterPaths as $path) {
            if (0 === strpos($feature->getFile(), $path)) {
                return true;
            }
        }

        return false;
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
        return $this->isFeatureMatch($scenario->getFeature());
    }
}
