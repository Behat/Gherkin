<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Filter interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @deprecated see either:
 *   * FeatureFilterInterface for finding all matching Scenarios within a Feature
 *   * ComplexFilterInterface to expose a method for checking if a single Scenario matches a filter
 */
interface FilterInterface extends FeatureFilterInterface
{
    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param ScenarioInterface $scenario Scenario or Outline node instance
     *
     * @return bool
     *
     * @deprecated see the interface PHPDoc for more details
     */
    public function isScenarioMatch(ScenarioInterface $scenario);
}
