<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\LineRangeFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use PHPUnit\Framework\Attributes\DataProvider;

class LineRangeFilterTest extends FilterTestCase
{
    /**
     * @return iterable<array{numeric-string, numeric-string|'*', bool}>
     */
    public static function featureLineRangeProvider(): iterable
    {
        return [
            ['1', '1', true],
            ['1', '2', true],
            ['1', '*', true],
            ['2', '2', false],
            ['2', '*', false],
        ];
    }

    /**
     * @param numeric-string $filterMinLine
     * @param numeric-string|'*' $filterMaxLine
     */
    #[DataProvider('featureLineRangeProvider')]
    public function testIsFeatureMatchFilter(string $filterMinLine, string $filterMaxLine, bool $expected): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $this->assertSame($expected, $filter->isFeatureMatch($feature));
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int, int|"*"}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield from self::providerFilterFeatureScenarioLineRange();
        yield from self::providerFilterFeatureScenarioExamples();
        yield from self::providerFilterFeatureOutlineExamples();
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int, int|"*"}>
     */
    private static function providerFilterFeatureScenarioLineRange(): iterable
    {
        yield 'scenario starts on last line of range' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2:     Scenario: Sample Scenario
                3: #   Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => true,
                    'Sample Outline' => false,
                ],
                stripLineNumbers: true,
            ),
            1,
            2,
        ];

        yield 'range covers entire file (*)' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2:     Scenario: Sample Scenario
                3:     Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => true,
                    'Sample Outline' => true,
                ],
                stripLineNumbers: true,
            ),
            1,
            '*',
        ];

        yield 'single-line range is Scenario line' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2:     Scenario: Sample Scenario
                3: #   Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => true,
                    'Sample Outline' => false,
                ],
                stripLineNumbers: true,
            ),
            2,
            2,
        ];

        yield 'range runs from Scenario line to EOF' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2:     Scenario: Sample Scenario
                3:     Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => true,
                    'Sample Outline' => true,
                ],
                stripLineNumbers: true,
            ),
            2,
            '*',
        ];

        yield 'single-line range is Outline line' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2: #  Scenario: Sample Scenario
                3:    Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => false,
                    'Sample Outline' => true,
                ],
                stripLineNumbers: true,
            ),
            3,
            3,
        ];

        yield 'range runs from Outline to EOF' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2: #  Scenario: Sample Scenario
                3:    Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => false,
                    'Sample Outline' => true,
                ],
                stripLineNumbers: true,
            ),
            3,
            '*',
        ];

        yield 'range only matches the Feature line' => [
            // No scenarios match, even though the feature itself will pass isFeatureMatch.
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2: #  Scenario: Sample Scenario
                3: #  Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => false,
                    'Sample Outline' => false,
                ],
                stripLineNumbers: true,
            ),
            1,
            1,
        ];

        yield 'range is beyond EOF' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2: #  Scenario: Sample Scenario
                3: #  Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => false,
                    'Sample Outline' => false,
                ],
                stripLineNumbers: true,
            ),
            4,
            4,
        ];

        yield 'range starts at EOF and runs to "*"' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1:   Feature:
                2: #  Scenario: Sample Scenario
                3: #  Scenario Outline: Sample Outline
                GHERKIN,
                expectScenarioMatches: [
                    'Sample Scenario' => false,
                    'Sample Outline' => false,
                ],
                stripLineNumbers: true,
            ),
            4,
            '*',
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int, int|"*"}>
     */
    private static function providerFilterFeatureScenarioExamples(): iterable
    {
        yield 'simple feature, range includes scenario line number' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios
                2 :   Scenario: Scenario#1
                3 :     Given initial step
                4 :     When action occurs
                5 :     Then outcomes should be visible
                6 : 
                7 : #  Scenario: Scenario#2
                8 : #    Given initial step
                9 : #    And another initial step
                10: #    When action occurs
                11: #    Then outcomes should be visible
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => true,
                    'Scenario#2' => false,
                ],
                stripLineNumbers: true,
            ),
            1,
            3,
        ];

        yield 'simple feature, range includes other scenario line number' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : #    When action occurs
                5 : #    Then outcomes should be visible
                6 : 
                7 :   Scenario: Scenario#2
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
            5,
            9,
        ];

        yield 'simple feature, range does not include any Scenario: line' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : #    When action occurs
                5 : #    Then outcomes should be visible
                6 : #
                7 : #  Scenario: Scenario#2
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
            6,
        ];

        yield 'simple feature, range ends with "*"' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Two scenarios
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : #    When action occurs
                5 : #    Then outcomes should be visible
                6 : 
                7 :   Scenario: Scenario#2
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
            5,
            '*',
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, int, int|"*"}>
     */
    private static function providerFilterFeatureOutlineExamples(): iterable
    {
        yield 'feature with outline, range includes one complete table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : # 
                5 :  
                6 :   Scenario Outline: Outline#1
                7 :     When <action> occurs
                8 :     Then <outcome> should be visible
                9 :  
                10:     @etag1
                11:     Examples:
                12:       | action | outcome |
                13:       | act#1  | out#1   |
                14:       | act#2  | out#2   |
                15:  
                16: #    @etag2
                17: #    Examples:
                18: #      | action | outcome |
                19: #      | act#3  | out#3   |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Outline#1' => true,
                ],
                stripLineNumbers: true,
            ),
            10,
            16,
        ];

        yield 'feature with outline, range includes both tables' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : # 
                5 :  
                6 :   Scenario Outline: Outline#1
                7 :     When <action> occurs
                8 :     Then <outcome> should be visible
                9 :  
                10:     @etag1
                11:     Examples:
                12:       | action | outcome |
                13:       | act#1  | out#1   |
                14:       | act#2  | out#2   |
                15:  
                16:     @etag2
                17:     Examples:
                18:       | action | outcome |
                19:       | act#3  | out#3   |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Outline#1' => true,
                ],
                stripLineNumbers: true,
            ),
            9,
            19,
        ];

        yield 'feature with outline, range includes one table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : # 
                5 :  
                6 :   Scenario Outline: Outline#1
                7 :     When <action> occurs
                8 :     Then <outcome> should be visible
                9 :  
                10: #    @etag1
                11: #    Examples:
                12: #      | action | outcome |
                13: #      | act#1  | out#1   |
                14: #      | act#2  | out#2   |
                15:  
                16:     @etag2
                17:     Examples:
                18:       | action | outcome |
                19:       | act#3  | out#3   |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Outline#1' => true,
                ],
                stripLineNumbers: true,
            ),
            18,
            19,
        ];

        yield 'feature with outline, range includes multiple partial tables' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : # 
                5 :  
                6 :   Scenario Outline: Outline#1
                7 :     When <action> occurs
                8 :     Then <outcome> should be visible
                9 :  
                10:     @etag1
                11:     Examples:
                12:       | action | outcome |
                13: #     | act#1  | out#1   |
                14:       | act#2  | out#2   |
                15:  
                16:     @etag2
                17:     Examples:
                18:       | action | outcome |
                19:       | act#3  | out#3   |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Outline#1' => true,
                ],
                stripLineNumbers: true,
            ),
            14,
            '*',
        ];

        yield 'feature with outline, range is one table row' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                1 : Feature: Long feature with outline
                2 : #  Scenario: Scenario#1
                3 : #    Given initial step
                4 : # 
                5 :  
                6 :   Scenario Outline: Outline#1
                7 :     When <action> occurs
                8 :     Then <outcome> should be visible
                9 :   
                10:      @etag1
                11:      Examples:
                12:        | action | outcome |
                13:        | act#1  | out#1   |
                14: #      | act#2  | out#2   |
                15: # 
                16: #    @etag2
                17: #    Examples:
                18: #      | action | outcome |
                19: #      | act#3  | out#3   |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Outline#1' => true,
                ],
                stripLineNumbers: true,
            ),
            13,
            13,
        ];

        yield 'matches one of example tables with different structures' => [
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
                18:      | act#4  | works  |
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario#1' => false,
                    'Scenario#2' => true,
                ],
                stripLineNumbers: true,
            ),
            17,
            18,
        ];

        yield 'matches multiple example tables with different structures' => [
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
                10:       | action | outcome  |
                11: #     | act#1  | whatever |
                12:       | act#2  | anything |
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
            12,
            17,
        ];
    }

    /**
     * @phpstan-param int|"*" $filterMaxLine
     */
    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, int $filterMinLine, string|int $filterMaxLine): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new LineRangeFilter($filterMinLine, $filterMaxLine));
    }

    /**
     * @return iterable<string, array{string, int, int}>
     */
    public static function providerFeaturesThatLeaveEmptyOutline(): iterable
    {
        $fixture = FeatureFilterTestFixture::fromCommentedExpectation(
            <<<'GHERKIN'
            1 : Feature: Long feature with outline
            2 :   Scenario: Scenario#1
            3 :     Given initial step
            4 :  
            5 :  
            6 :   Scenario Outline: Outline#1
            7 :     When <action> occurs
            8 :     Then <outcome> should be visible
            9 :   
            10:      @etag1
            11:      Examples: First set
            12:        | action | outcome |
            13:        | act#1  | out#1   |
            14:        | act#2  | out#2   |
            15:  
            16:     @etag2
            17:      Examples: Second set
            18:        | action | outcome |
            19:        | act#3  | out#3   |
            GHERKIN,
            expectScenarioMatches: [
                'Scenario#1' => false,
                'Outline#1' => true,
            ],
            stripLineNumbers: true,
        );

        yield 'line range includes Outline: but no tables' => [
            $fixture->originalFeature,
            6,
            9,
        ];

        yield 'line range includes Outline: and Examples: but no table rows' => [
            $fixture->originalFeature,
            6,
            11,
        ];

        yield 'line range includes Outline: through to table header, but no table rows' => [
            // This is inconsistent with LineFilter, where matching the table header row keeps the Outline
            // with an empty table (containing only the header row).
            $fixture->originalFeature,
            6,
            12,
        ];
    }

    #[DataProvider('providerFeaturesThatLeaveEmptyOutline')]
    public function testFilterFeatureLeavesEmptyOutlineIfNoTableRowsInRange(
        string $originalFeature,
        int $filterMinLine,
        int $filterMaxLine,
    ): void {
        // Edge case: If the range includes the Outline: line but none of the Examples: table rows, the filtered feature
        // will have an empty Outline. We can't prove this with the normal test, because the parser converts an empty
        // Outline to a Scenario node.
        $feature = $this->parseFeature($originalFeature);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $filtered = $filter->filterFeature($feature);

        $this->assertTrue($filtered->hasScenarios(), 'Feature still has scenarios');

        $filteredScenarios = $filtered->getScenarios();
        $this->assertCount(1, $filteredScenarios, 'Only a single scenario matches');

        $filteredOutline = $filteredScenarios[0];
        $this->assertInstanceOf(OutlineNode::class, $filteredOutline, 'Filtered scenario is an Outline');
        $this->assertFalse($filteredOutline->hasExamples(), 'Filtered scenario has no examples');
    }
}
