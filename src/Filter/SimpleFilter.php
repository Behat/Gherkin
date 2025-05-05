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

/**
 * Abstract filter class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class SimpleFilter implements FilterInterface
{
    /**
     * Filters feature according to the filter.
     *
     * @return FeatureNode
     */
    public function filterFeature(FeatureNode $feature)
    {
        if ($this->isFeatureMatch($feature)) {
            return $feature;
        }

        return $feature->withScenarios(
            array_filter(
                $feature->getScenarios(),
                $this->isScenarioMatch(...)
            )
        );
    }
}
