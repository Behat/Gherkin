<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\LineFilter;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\Attributes\DataProvider;

class LineFilterTest extends FilterTestCase
{
    public function testIsFeatureMatchFilter(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $filter = new LineFilter(1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new LineFilter(2);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new LineFilter(3);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield from self::providerFilterFeatureScenarios();
        yield from self::providerFilterFeatureOutlineExamples();
        yield from self::providerFilterFeatureRuleExamples();
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int}>
     */
    private static function providerFilterFeatureScenarios(): iterable
    {
        yield 'simple feature, exact scenario line number' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios                 
                2 :   Scenario: Scenario#1                 
                3 :     Given initial step                 
                4 :     When action occurs                 
                5 :     Then outcomes should be visible    
                6 :                                        
                7 : #   Scenario: Scenario#2                 
                8 : #     Given initial step                 
                9 : #     And another initial step           
                10: #     When action occurs                 
                11: #    Then outcomes should be visible     
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => true,
                    'Scenario#2' => false,
                ],
                stripLineNumbers: true,
            ),
            2,
        ];

        yield 'simple feature, other matching line number' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios                 
                2 : #  Scenario: Scenario#1                 
                3 : #    Given initial step                 
                4 : #    When action occurs                 
                5 : #    Then outcomes should be visible    
                6 :                                        
                7 :    Scenario: Scenario#2                 
                8 :     Given initial step                 
                9 :     And another initial step           
                10:     When action occurs                 
                11:     Then outcomes should be visible     
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            7,
        ];

        yield 'simple feature, within a scenario (matches nothing)' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios                 
                2 : #  Scenario: Scenario#1                 
                3 : #    Given initial step                 
                4 : #    When action occurs                 
                5 : #    Then outcomes should be visible    
                6 : #                                       
                7 : #   Scenario: Scenario#2                 
                8 : #    Given initial step                 
                9 : #    And another initial step           
                10: #    When action occurs                 
                11: #    Then outcomes should be visible     
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => false,
                ],
                stripLineNumbers: true,
            ),
            5,
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int}>
     */
    private static function providerFilterFeatureOutlineExamples(): iterable
    {
        yield 'feature with outline, matches line of the Outline' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 :   Scenario Outline: Scenario#2
                6 :     When <action> occurs
                8 :
                9 :     @etag1
                10:     Examples:
                11:       | action |
                12:       | act#1  |
                13:       | act#2  |
                14:
                15:    @etag2
                16:    Examples:
                17:      | action |
                18:      | act#3  |
                19:      | act#4  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            5,
        ];

        yield 'feature with outline, matches a step in the Outline (matches nothing)' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 : #  Scenario Outline: Scenario#2
                6 : #    When <action> occurs
                7 : #
                8 : #    @etag1
                9 : #    Examples:
                10: #      | action |
                11: #      | act#1  |
                12: #      | act#2  |
                13: #
                14: #   @etag2
                15: #   Examples:
                16: #     | action |
                17: #     | act#3  |
                18: #     | act#4  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => false,
                ],
                stripLineNumbers: true,
            ),
            6,
        ];

        yield 'feature with outline, matches one line in Example table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 :   Scenario Outline: Scenario#2
                6 :     When <action> occurs
                7 :
                8 :     @etag1
                9 :     Examples:
                10:       | action |
                11: #     | act#1  |
                12:       | act#2  |
                13:
                14: #  @etag2
                15: #  Examples:
                16: #    | action |
                17: #    | act#3  |
                18: #    | act#4  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            12,
        ];

        yield 'feature with outline, matches different line in Example table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 :   Scenario Outline: Scenario#2
                6 :     When <action> occurs
                7 :
                8 : #   @etag1
                9 : #   Examples:
                10: #     | action |
                11: #     | act#1  |
                12: #     | act#2  |
                13:
                14:    @etag2
                15:    Examples:
                16:      | action |
                17:      | act#3  |
                18: #    | act#4  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            17,
        ];

        yield 'feature with outline, matches one Example table header (parses as empty table, matches Scenario)' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 :   Scenario Outline: Scenario#2
                6 :     When <action> occurs
                7 :
                8 :    @etag1
                9 :    Examples:
                10:       | action | 
                11: #     | act#1  |
                12: #
                13: #   Examples:
                14: #     | action |
                15: #     | act#3  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            10,
        ];

        yield 'feature with outline, Examples: line matches nothing' => [
            // This is current behaviour, but it is slightly unexpected (and inconsistent with the behaviour of matching
            // either the Outline, or the header of the table)
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1: Feature: Some feature
                2: 
                3: #  Scenario Outline: Some scenario
                4: #    When <action> occurs
                5: #
                6: #   @etag1
                7: #   Examples:
                8: #     | action | outcome |
                9: #     | act#1  | out#1   |
                GHERKIN,
                expectScenarioMatches: [
                    'Some scenario' => false,
                ],
                stripLineNumbers: true,
            ),
            7,
        ];

        yield 'matches example tables with different structures' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #   Given initial step
                4 :
                5 :   Scenario Outline: Scenario#2
                6 :     When <action> occurs
                7 :
                8 : #   @etag1
                9 : #   Examples:
                10: #     | action | outcome  |
                11: #     | act#1  | whatever |
                12: #     | act#2  | anything |
                13:
                14:    @etag2
                15:    Examples:
                16:      | action | result |
                17:      | act#3  | ?      |
                18: #    | act#4  | works  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            17,
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int}>
     */
    private static function providerFilterFeatureRuleExamples(): iterable
    {
        yield 'can match on Scenario line without losing Rule structure' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : @feature-tag
                2 : Feature: Some work in progress
                3 : 
                4 :   @rule-tag
                5 :   Rule: Rule 1
                6 :     
                7 :      Background:
                8 :        Given rule background
                9 :        
                10:      @web
                11:      Scenario: Scenario 1
                12:        Given anything
                13:        
                14: #     @browser
                15: #     Scenario Outline: Scenario 2
                16: #       Given <something>
                17: #    
                18: #       @example-tag
                19: #       Examples: First set of examples
                20: #        | something | 
                21: #        | here      |
                GHERKIN,
                expectScenarioMatches: [
                    'Rule 1' => [
                        'Scenario 1' => true,
                        'Scenario 2' => false,
                    ],
                ],
                stripLineNumbers: true,
            ),
            11,
        ];

        yield 'can match on Examples table row without losing Rule structure' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : @feature-tag
                2 : Feature: Some work in progress
                3 : 
                4 :   @rule-tag
                5 :   Rule: Rule 1
                6 :     
                7 :      Background:
                8 :        Given rule background
                9 :        
                10: #     @web
                11: #     Scenario: Scenario 1
                12: #       Given anything
                13:        
                14:      @browser
                15:      Scenario Outline: Scenario 2
                16:        Given <something>
                17:     
                18:        @example-tag
                19:        Examples: First set of examples
                20:         | something | 
                21:         | here      |
                22: #       | another   |
                GHERKIN,
                expectScenarioMatches: [
                    'Rule 1' => [
                        'Scenario 1' => false,
                        'Scenario 2' => true,
                    ],
                ],
                stripLineNumbers: true,
            ),
            21,
        ];

        yield 'Drops empty rules if no matched line' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : @feature-tag
                2 : Feature: Some work in progress
                3 : 
                4 : #  @rule-tag
                5 : #  Rule: Rule 1
                6 : #    
                7 : #     Background:
                8 : #       Given rule background
                9 : #       
                10: #     @web
                11: #     Scenario: Scenario 1
                12: #       Given anything
                GHERKIN,
                expectScenarioMatches: [
                    'Rule 1' => [
                        'Scenario 1' => false,
                    ],
                ],
                stripLineNumbers: true,
            ),
            9,
        ];

        yield 'Matches all Scenarios in a Rule given the Rule line' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : @feature-tag
                2 : Feature: Some work in progress
                3 : 
                4 : #  @rule-tag
                5 : #  Rule: Rule 1
                6 : #    
                7 : #     Background:
                8 : #       Given rule background
                9 : #       
                10: #     @web
                11: #     Scenario: Scenario 1
                12: #       Given anything
                13: #       
                14: #     @browser
                15: #     Scenario Outline: Scenario 2
                16: #       Given <something>
                17: #    
                18: #       @example-tag
                19: #       Examples: First set of examples
                20: #        | something | 
                21: #        | here      |
                22: 
                23:   @rule-tag
                24:   Rule: Rule 2
                25:
                26:     Background:
                27:       Given rule background
                28:    
                29:     Scenario: Scenario 3
                30:       Given whatever
                31:
                32:     @browser
                33:     Scenario Outline: Scenario 4
                34:        Given <something>
                35:     
                36:        @example-tag
                37:        Examples: First set of examples
                38:         | something | 
                39:         | here      |
                GHERKIN,
                expectScenarioMatches: [
                    'Rule 1' => [
                        'Scenario 1' => false,
                        'Scenario 2' => false,
                    ],
                    'Rule 2' => [
                        // NOTE: deprecated `isScenarioMatch` does NOT match against the Rule's line number, even though
                        // this is considered for `filterFeature`.
                        // This is similar to the inconsistency in `NameFilter` where the `filterFeature` considers
                        // text in parent nodes, but `isScenarioMatch` only considers the Scenario itself.
                        'Scenario 3' => false,
                        'Scenario 4' => false,
                    ],
                ],
                stripLineNumbers: true,
            ),
            24,
        ];
    }

    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, int $filterLine): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new LineFilter($filterLine));
    }
}
