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
        $nonFilteringFilter = new class extends ComplexFilter {
            public function isScenarioMatch(FeatureNode $feature, ScenarioInterface $scenario): bool
            {
                return true;
            }

            public function isFeatureMatch(FeatureNode $feature): bool
            {
                return true;
            }
        };

        $filtered = $nonFilteringFilter->filterFeature($feature);

        $this->assertCount(1, $scenarios = $filtered->getScenarios());
        $this->assertSame('Scenario#1', $scenarios[0]->getTitle());
    }
}
