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
 */
abstract class ComplexFilter extends AbstractFeatureFilter implements ComplexFilterInterface
{
    public function __construct(bool $skipFilteringChildrenIfFeatureMatches = false)
    {
        // By default, ComplexFilter implementations need to check the individual children, not the overall feature
        parent::__construct(skipFilteringChildrenIfFeatureMatches: $skipFilteringChildrenIfFeatureMatches);
    }

    protected function filterScenario(FeatureNode $feature, ?RuleNode $rule, ScenarioInterface $scenario): ScenarioInterface|false
    {
        return $this->isScenarioMatch($feature, $scenario) ? $scenario : false;
    }
}
