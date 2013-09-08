<?php

namespace Behat\Gherkin\Filter;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filter interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface FilterInterface
{
    /**
     * Checks if Feature matches specified filter.
     *
     * @param FeatureNode $feature Feature instance
     *
     * @return Boolean
     */
    public function isFeatureMatch(FeatureNode $feature);

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return Boolean
     */
    public function isScenarioMatch(ScenarioInterface $scenario);

    /**
     * Filters feature according to the filter and returns new one.
     *
     * @param FeatureNode $feature
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature);
}
