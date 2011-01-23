<?php

namespace Behat\Gherkin\Filter;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\StepNode;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Filter Interface.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface FilterInterface
{
    /**
     * Check If Feature Matches Specified Filter. 
     * 
     * @param   FeatureNode     $feature    feature
     */
    function isFeatureMatch(FeatureNode $feature);

    /**
     * Check If Scenario Or Outline Matches Specified Filter. 
     * 
     * @param   ScenarioNode|OutlineNode    $scenario   scenario or outline
     */
    function isScenarioMatch(ScenarioNode $scenario);
}
