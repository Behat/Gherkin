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
 * Filters features by their narrative using regular expression.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NarrativeFilter extends SimpleFilter
{
    public function __construct(
        private readonly string $regex,
    ) {
    }

    public function isFeatureMatch(FeatureNode $feature)
    {
        return (bool) preg_match($this->regex, $feature->getDescription() ?? '');
    }

    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // This filter does not apply to scenarios.
        return false;
    }
}
