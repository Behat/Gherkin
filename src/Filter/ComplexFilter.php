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
 * Abstract filter class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class ComplexFilter implements ComplexFilterInterface
{
    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        $scenarios = $feature->getScenarios();
        $filteredScenarios = array_filter(
            $scenarios,
            fn (ScenarioInterface $scenario) => $this->isScenarioMatch($feature, $scenario)
        );

        return $scenarios === $filteredScenarios ? $feature : $feature->withScenarios($filteredScenarios);
    }
}
