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
 *
 * @phpstan-ignore class.extendsDeprecatedClass (Needs to keep the existing interface for BC)
 */
class NarrativeFilter extends SimpleFilter
{
    public function __construct(
        private readonly string $regex,
    ) {
        // If the feature name matches, we include it unchanged without any filtering of children
        parent::__construct(skipFilteringChildrenIfFeatureMatches: true);
    }

    public function isFeatureMatch(FeatureNode $feature)
    {
        return (bool) preg_match($this->regex, $feature->getDescription() ?? '');
    }

    /**
     * @deprecated see FilterInterface for further information
     */
    public function isScenarioMatch(ScenarioInterface $scenario)
    {
        // This filter does not apply to scenarios.
        return false;
    }
}
