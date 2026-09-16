<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\SimpleFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @phpstan-type TFilterMatcherFuncs array{feature: Closure(FeatureNode):bool, scenario: Closure(ScenarioInterface):bool}
 */
class SimpleFilterTest extends FilterTestCase
{
    public function testFilterFeatureShouldReturnSameInstanceWhenNotFiltering(): void
    {
        $originalFeature = $this->parseFeature(
            <<<'GHERKIN'
            Feature: A feature
              
              Scenario: A scenario 
            GHERKIN,
        );

        $nonFilteringFilter = $this->createSimpleFilter(
            ['feature' => static fn () => true, 'scenario' => static fn () => true],
        );

        $this->assertSame($originalFeature, $nonFilteringFilter->filterFeature($originalFeature));
    }

    /**
     * @phpstan-return iterable<string, array{FeatureFilterTestFixture, TFilterMatcherFuncs}>
     */
    public static function providerFilterFeatureWhenFiltering(): iterable
    {
        yield 'includes all scenarios (without checking individually) if Feature itself matches' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: A feature
                  
                  Scenario: Scenario 1
                    Given something
                  
                  Scenario: Scenario 2
                    Given something  

                GHERKIN,
                [
                    // Note isScenarioMatch returns false, but filterFeature never called it
                    // This is the pattern of our existing SimpleFilter implementations.
                    'Scenario 1' => false,
                    'Scenario 2' => false,
                ],
            ),
            [
                'feature' => static fn (FeatureNode $f) => $f->getTitle() === 'A feature',
                'scenario' => static fn () => false,
            ],
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
                GHERKIN,
                [
                    'Scenario 1' => false,
                    'Scenario 2' => true,
                    'Scenario 3' => false,
                ],
            ),
            [
                'feature' => static fn () => false,
                'scenario' => static fn (ScenarioInterface $s): bool => $s->getTitle() === 'Scenario 2',
            ],
        ];
    }

    /**
     * @phpstan-param TFilterMatcherFuncs $matchers
     */
    #[DataProvider('providerFilterFeatureWhenFiltering')]
    public function testFilterFeatureShouldReturnDifferentInstanceWhenFilteringOutAllScenarios(FeatureFilterTestFixture $testCase, array $matchers): void
    {
        $this->assertFiltersFeatureAsExpected($testCase, $this->createSimpleFilter($matchers));
    }

    /**
     * @phpstan-param TFilterMatcherFuncs $matchers
     *
     * @phpstan-ignore return.deprecatedClass (Testing for BC)
     */
    private function createSimpleFilter(array $matchers): SimpleFilter
    {
        /* @phpstan-ignore class.extendsDeprecatedClass (Testing for BC) */
        return new class($matchers['feature'], $matchers['scenario']) extends SimpleFilter {
            /**
             * @param Closure(FeatureNode): bool $featureMatcher
             * @param Closure(ScenarioInterface): bool $scenarioMatcher
             */
            public function __construct(
                private readonly Closure $featureMatcher,
                private readonly Closure $scenarioMatcher,
            ) {
            }

            public function isFeatureMatch(FeatureNode $feature): bool
            {
                return ($this->featureMatcher)($feature);
            }

            public function isScenarioMatch(ScenarioInterface $scenario): bool
            {
                return ($this->scenarioMatcher)($scenario);
            }
        };
    }
}
