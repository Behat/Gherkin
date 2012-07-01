<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param FeatureNode $feature
     */
    public function filterFeature(FeatureNode $feature)
    {
        $scenarios = $feature->getScenarios();
        foreach ($scenarios as $i => $scenario) {
            if (!$this->isScenarioMatch($scenario)) {
                unset($scenarios[$i]);
            }
        }

        $feature->setScenarios($scenarios);
    }
}
