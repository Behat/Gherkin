<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filter interface.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface FilterInterface
{
    /**
     * Checks if Feature matches specified filter.
     *
     * @param   Behat\Gherkin\Node\FeatureNode  $feature
     */
    function isFeatureMatch(FeatureNode $feature);

    /**
     * Checks if scenario or outline matches specified filter.
     *
     * @param   Behat\Gherkin\Node\ScenarioNode|Behat\Gherkin\Node\OutlineNode  $scenario
     */
    function isScenarioMatch(ScenarioNode $scenario);
}
