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
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;

/**
 * Abstract filter class.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @deprecated see FilterInterface for further information
 */
abstract class SimpleFilter extends AbstractFeatureFilter implements FilterInterface
{
    protected function filterScenario(FeatureNode $feature, ?RuleNode $rule, ScenarioInterface $scenario): ScenarioInterface|false
    {
        return $this->isScenarioMatch($scenario) ? $scenario : false;
    }
}
