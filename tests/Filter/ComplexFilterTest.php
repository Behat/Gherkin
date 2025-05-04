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
use Closure;
use RuntimeException;

class ComplexFilterTest extends FilterTestCase
{
    public function testFilterFeatureShouldReturnSameInstanceWhenNotFiltering(): void
    {
        $scenarios = [new ScenarioNode('A scenario', [], [], '', 1)];
        $originalFeature = new FeatureNode('A feature', null, [], null, $scenarios, '', '', null, 1);
        $nonFilteringFilter = $this->createComplexFilter(static fn () => true);

        $filteredFeature = $nonFilteringFilter->filterFeature($originalFeature);

        $this->assertSame($originalFeature, $filteredFeature);
    }

    public function testFilterFeatureShouldReturnDifferentInstanceWhenFilteringOutAllScenarios(): void
    {
        $scenarios = [new ScenarioNode('A scenario', [], [], '', 1)];
        $originalFeature = new FeatureNode('A feature', null, [], null, $scenarios, '', '', null, 1);
        $nonFilteringFilter = $this->createComplexFilter(static fn () => false);

        $filteredFeature = $nonFilteringFilter->filterFeature($originalFeature);

        $this->assertNotSame($originalFeature, $filteredFeature);
        $this->assertFalse($filteredFeature->hasScenarios());
    }

    public function testFilterFeatureShouldReturnDifferentInstanceWhenFilteringScenarios(): void
    {
        $scenarios = [
            $scenario1 = new ScenarioNode('Scenario#1', [], [], '', 1),
            $scenario2 = new ScenarioNode('Scenario#2', [], [], '', 2),
            $scenario3 = new ScenarioNode('Scenario#3', [], [], '', 3),
        ];
        $originalFeature = new FeatureNode('Feature#1', null, [], null, $scenarios, '', '', null, 1);
        $nonFilteringFilter = $this->createComplexFilter(static fn ($feature, $scenario) => $scenario !== $scenario2);

        $filteredFeature = $nonFilteringFilter->filterFeature($originalFeature);

        $this->assertNotSame($originalFeature, $filteredFeature);
        $this->assertSame([$scenario1, $scenario3], $filteredFeature->getScenarios());
    }

    /**
     * @param Closure(FeatureNode, ScenarioInterface): bool $scenarioMatcher
     */
    private function createComplexFilter(Closure $scenarioMatcher): ComplexFilter
    {
        return new class($scenarioMatcher) extends ComplexFilter {
            /**
             * @param Closure(FeatureNode, ScenarioInterface): bool $scenarioMatcher
             */
            public function __construct(
                private readonly Closure $scenarioMatcher,
            ) {
            }

            public function isFeatureMatch(FeatureNode $feature): bool
            {
                throw new RuntimeException('Not implemented');
            }

            public function isScenarioMatch(FeatureNode $feature, ScenarioInterface $scenario): bool
            {
                return ($this->scenarioMatcher)($feature, $scenario);
            }
        };
    }
}
