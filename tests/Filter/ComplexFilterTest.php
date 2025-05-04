<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\ComplexFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;

class ComplexFilterTest extends FilterTestCase
{
    public function testFilterFeature(): void
    {
        $scenarios = [new ScenarioNode('Scenario#1', [], [], '', 1)];
        $feature = new FeatureNode('Feature#1', null, [], null, $scenarios, '', '', null, 1);

        $filter = $this->createComplexFilter([$feature], $scenarios);

        $filtered = $filter->filterFeature($feature);

        $this->assertCount(1, $scenarios = $filtered->getScenarios());
        $this->assertSame('Scenario#1', $scenarios[0]->getTitle());
    }

    /**
     * @param list<FeatureNode> $matchingFeatures
     * @param list<ScenarioInterface> $matchingScenarios
     */
    private function createComplexFilter(array $matchingFeatures, array $matchingScenarios): ComplexFilter
    {
        return new class($matchingFeatures, $matchingScenarios) extends ComplexFilter {
            /**
             * @param list<FeatureNode> $matchingFeatures
             * @param list<ScenarioInterface> $matchingScenarios
             */
            public function __construct(
                private readonly array $matchingFeatures,
                private readonly array $matchingScenarios,
            ) {
            }

            public function isScenarioMatch(FeatureNode $feature, ScenarioInterface $scenario)
            {
                return in_array($feature, $this->matchingFeatures, true)
                    && in_array($scenario, $this->matchingScenarios, true);
            }

            public function isFeatureMatch(FeatureNode $feature)
            {
                return in_array($feature, $this->matchingFeatures, true);
            }
        };
    }
}
