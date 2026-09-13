<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Filter\ComplexFilterInterface;
use Behat\Gherkin\Filter\FeatureFilterInterface;
use Behat\Gherkin\Filter\FilterInterface;
use Behat\Gherkin\GherkinCompatibilityMode;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

abstract class FilterTestCase extends TestCase
{
    protected function getParser(GherkinCompatibilityMode $mode): Parser
    {
        return new Parser(
            new Lexer(
                new CucumberDialectProvider(),
            ),
            $mode,
        );
    }

    final protected function parseFeature(string $feature, ?string $featureFilePath = null): FeatureNode
    {
        return $this->getParser(GherkinCompatibilityMode::GHERKIN_32)->parse($feature, $featureFilePath)
            ?? throw new \InvalidArgumentException('Could not parse predefined test feature');
    }

    final protected function assertFiltersFeatureAsExpected(FeatureFilterTestFixture $testcase, FeatureFilterInterface $filter, ?string $featureFilePath = null): void
    {
        $originalFeature = $this->parseFeature($testcase->originalFeature, $featureFilePath);

        // First, assert that `isScenarioMatch` matches all scenarios within the feature as expected.
        // This also ensures that the original feature has been parsed to the expected list of scenarios (e.g. that
        // anything that was commented in the expected feature has been properly uncommented).
        $actualScenarioMatches = [];
        foreach ($originalFeature->getScenarios() as $scenario) {
            $title = $scenario->getTitle() ?? '';

            if (isset($actualScenarioMatches[$title])) {
                throw new UnexpectedValueException('Duplicate scenario title in test data: ' . $title);
            }

            $actualScenarioMatches[$scenario->getTitle() ?? ''] = match (true) {
                $filter instanceof FilterInterface => $filter->isScenarioMatch($scenario),
                $filter instanceof ComplexFilterInterface => $filter->isScenarioMatch($originalFeature, $scenario),
                default => throw new RuntimeException('Unknown filter type'),
            };
        }
        $this->assertSame($testcase->expectScenarioMatches, $actualScenarioMatches);

        // Then, assert that the result of filtering the feature is identical to parsing an equivalent feature
        $filteredFeature = $filter->filterFeature($originalFeature);

        $this->assertEquals(
            $this->parseFeature($testcase->expectedEquivalentFeature, $featureFilePath),
            $filteredFeature,
            'Filtered feature should match the expected equivalent feature'
        );
    }
}
