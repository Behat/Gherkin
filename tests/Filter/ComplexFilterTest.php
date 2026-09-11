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
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class ComplexFilterTest extends FilterTestCase
{
    public function testFilterFeatureShouldReturnSameInstanceWhenNotFiltering(): void
    {
        $originalFeature = $this->parseFeature(
            <<<'GHERKIN'
            Feature: A feature
              
              Scenario: A scenario 
            GHERKIN
        );

        $nonFilteringFilter = $this->createComplexFilter(static fn () => true);

        $this->assertSame($originalFeature, $nonFilteringFilter->filterFeature($originalFeature));
    }

    /**
     * @phpstan-return iterable<string, array{FeatureFilterTestFixture, Closure(FeatureNode, ScenarioInterface): bool}>
     */
    public static function providerFilterFeatureWhenFiltering(): iterable
    {
        yield 'filters out all scenarios' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: A feature
                  
                #  Scenario: Scenario 1
                #    Given something
                #  
                #  Scenario: Scenario 2
                #    Given something  

                GHERKIN, [
                    'Scenario 1' => false,
                    'Scenario 2' => false,
                ]
            ),
            static fn () => false,
        ];

        yield 'partially filters scenarios' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: A feature
                  With a description

                #  Scenario: Scenario 1
                #    Given something

                   Scenario: Scenario 2
                     Given something  
                  
                #  Scenario: Scenario 3
                #    Given other
                GHERKIN, [
                    'Scenario 1' => false,
                    'Scenario 2' => true,
                    'Scenario 3' => false,
                ]
            ),
            static fn (FeatureNode $f, ScenarioInterface $s): bool => $s->getTitle() === 'Scenario 2',
        ];

        yield 'keeps Rule structure when filtering' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: A feature
                    With a description
                  
                  Background:
                    Given global background
                    
                #  Scenario: Top-level Scenario
                    
                  Rule: Rule 1
                  
                    Background:
                      Given rule background
                  
                #   Scenario: Scenario 1
                #     Given something
                    
                    Scenario: Scenario 2
                      Given something else
                    
                #  Rule: Rule 2
                #
                #    Background:
                #      Given other rule background
                #    
                #    Scenario: Scenario 3
                #      Given whatever
                GHERKIN,
                expectScenarioMatches: [
                    'Top-level Scenario' => false,
                    'Rule 1' => [
                        'Scenario 1' => false,
                        'Scenario 2' => true,
                    ],
                    'Rule 2' => [
                        'Scenario 3' => false,
                    ],
                ],
            ),
            static fn (FeatureNode $f, ScenarioInterface $s): bool => $s->getTitle() === 'Scenario 2',
        ];
    }

    /**
     * @phpstan-param Closure(FeatureNode, ScenarioInterface): bool $filterFunc
     */
    #[DataProvider('providerFilterFeatureWhenFiltering')]
    public function testFilterFeatureShouldReturnDifferentInstanceWhenFilteringOutAllScenarios(FeatureFilterTestFixture $testCase, callable $filterFunc): void
    {
        $this->assertFiltersFeatureAsExpected($testCase, $this->createComplexFilter($filterFunc));
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
