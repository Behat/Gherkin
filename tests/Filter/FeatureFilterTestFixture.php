<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

/**
 * Represents an original Gherkin feature with the expectations of what it should look like after filtering.
 *
 * If filters are working correctly, the result of every `filterFeature` operation should be identical to what
 * we would have parsed if the nodes that don't match the filter had never been present.
 *
 * This means that we can express the expected behaviour of a filter as:
 *
 *  - A Gherkin feature string to parse as the input to the filter.
 *  - A Gherkin feature string that, when parsed, should be equivalent to the filtered feature.
 *
 * Building assertions in this way avoids us being coupled to the details of how the Parser creates nodes and the
 * properties they have. It also ensures that filters do not unexpectedly modify the structure or content of nodes
 * during filtering.
 *
 * @phpstan-type TExpectedScenarioMatches array<string, bool>
 */
final class FeatureFilterTestFixture
{
    /**
     * The filter is expected to match everything - the filtered result should be identical to re-parsing the original.
     *
     * @phpstan-param TExpectedScenarioMatches $expectScenarioMatches
     */
    public static function expectingNoFiltering(
        string $feature,
        array $expectScenarioMatches,
    ): self {
        return new self(
            originalFeature: $feature,
            expectedEquivalentFeature: $feature,
            expectScenarioMatches: $expectScenarioMatches,
        );
    }

    /**
     * The filter is expected to remove some nodes.
     *
     * The filtered feature should be identical to what would be parsed if the nodes that don't match were commented out.
     *
     * Therefore, we can keep tests clear and concise by providing the expected, commented, result. This method then
     * un-comments all lines to produce the input feature.
     *
     * For example:
     *
     *    <<<'GHERKIN'
     *      Feature: Something
     *
     *      #  Scenario: First
     *      #    Given something
     *    GHERKIN;
     *
     * Will produce a test fixture where the "original feature" is:
     *
     *    <<<'GHERKIN'
     *       Feature: Something
     *
     *         Scenario: First
     *           Given something
     *     GHERKIN;
     *
     * The test parses this as a feature with a single scenario, then asserts that all scenarios were filtered out of
     * the filtered feature.
     *
     * Some filters are based on line numbers. To make the tests clear, you can include line numbering in your feature
     * string and then pass `stripLineNumbers` to remove those before the feature is parsed. See the LineFilterTest
     * for examples.
     *
     * @phpstan-param TExpectedScenarioMatches $expectScenarioMatches
     */
    public static function fromCommentedExpectation(
        string $expectEquivalentFeature,
        array $expectScenarioMatches,
        bool $stripLineNumbers = false,
    ): self {
        if ($stripLineNumbers) {
            $expectEquivalentFeature = (string) preg_replace('/^\d+\s*:/m', '', $expectEquivalentFeature);
        }

        return new self(
            originalFeature: (string) preg_replace('/^(\s*)#/m', '$1 ', $expectEquivalentFeature),
            expectedEquivalentFeature: $expectEquivalentFeature,
            expectScenarioMatches: $expectScenarioMatches,
        );
    }

    /**
     * @phpstan-param TExpectedScenarioMatches $expectScenarioMatches
     */
    public function __construct(
        public readonly string $originalFeature,
        public readonly string $expectedEquivalentFeature,
        public readonly array $expectScenarioMatches,
    ) {
    }
}
