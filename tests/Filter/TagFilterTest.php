<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

class TagFilterTest extends FilterTestCase
{
    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield from self::filterFeatureTaggedScenarios();
        yield from self::providerFilterFeatureTaggedExamples();
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    private static function filterFeatureTaggedScenarios(): iterable
    {
        yield 'filters on feature tag' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @wip
                Feature: Some work in progress

                  Scenario: Things are implemented
                    Given things are implemented 
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@wip',
        ];

        yield 'filters on negated feature tag' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @wip
                Feature: Some work in progress

                #  Scenario: Things are implemented
                #    Given things are implemented 
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => false,
                ],
            ),
            '~@wip',
        ];

        yield 'filters on a scenario tag' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some work in progress

                #  Scenario: Things are implemented
                #    Given things are implemented
                    
                  @wip
                  Scenario: Still working on this   
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => false,
                    'Still working on this' => true,
                ],
            ),
            '@wip',
        ];

        yield 'filters on a negated scenario tag' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some work in progress

                  Scenario: Things are implemented
                    Given things are implemented
                    
                #  @wip
                #  Scenario: Still working on this   
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Still working on this' => false,
                ],
            ),
            '~@wip',
        ];

        yield 'filters AND on union of feature and scenario tags' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @users
                Feature: Something with users

                  @complete
                  Scenario: Things are implemented
                    Given things are implemented
                    
                #  @wip
                #  Scenario: Still working on this
                #    Given other things   
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Still working on this' => false,
                ],
            ),
            '@users && @complete',
        ];

        yield 'filters on OR condition' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @users
                Feature: Something with users

                  @complete
                  Scenario: Things are implemented
                    Given things are implemented

                #  Scenario: Still working on this
                #    Given other things

                  @web
                  Scenario: Scenario 3     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Still working on this' => false,
                    'Scenario 3' => true,
                ],
            ),
            '@complete, @web',
        ];

        yield 'Not affected by duplicate tags in hierarchy' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @users
                Feature: Something with users

                  @complete
                  Scenario: Things are implemented
                    Given things are implemented

                  Scenario: Still working on this
                    Given other things

                  @users
                  Scenario: Scenario 3     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Still working on this' => true,
                    'Scenario 3' => true,
                ],
            ),
            '@users',
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    private static function providerFilterFeatureTaggedExamples(): iterable
    {
        yield 'includes all tables if Feature matches the tag' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@feature-tag',
        ];

        yield 'includes all tables if Outline matches the tag' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@wip',
        ];

        yield 'includes all tables if all tables match the tag' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@etag2',
        ];

        yield 'includes only tagged table matching tags' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                #    @etag2 @etag3
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@etag1',
        ];

        yield 'removes example table matching negated tag' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                #    @etag2 @etag3
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '~@etag3',
        ];

        yield 'matches outline & one example table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                #    @etag1 @etag2
                #    Examples: First set of examples
                #      | something | 
                #      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |
                      
                #  @other-scenario
                #  Scenario Outline: Scenario that does not match
                #    Given <something>
                #    
                #    @etag1 @etag2
                #    Examples: First set of examples
                #      | something | 
                #      | here      |
                #      
                #    @etag2 @etag3
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Scenario that does not match' => false,
                ],
            ),
            '@wip && @etag3',
        ];

        yield 'matches feature, outline, and table' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                #    @etag2 @etag3
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |

                #  @other-scenario
                #  Scenario Outline: Scenario that does not match
                #    Given <something>
                #    
                #    @etag1 @etag2
                #    Examples: First set of examples
                #      | something | 
                #      | here      |
                #      
                #    @etag2 @etag3
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |

                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                    'Scenario that does not match' => false,
                ],
            ),
            '@feature-tag && @etag1 && @wip',
        ];

        yield 'ignores negated tag that is not present in the feature' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@feature-tag && ~@etag11111 && @wip',
        ];

        yield 'matches feature but one example matches negated tag' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                #    @etag1 @etag2
                #    Examples: First set of examples
                #      | something | 
                #      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@feature-tag && ~@etag1 && @wip',
        ];

        yield 'matches feature and tag present on both example tables' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress

                  @wip
                  Scenario Outline: Things are implemented
                    Given <something>
                    
                    @etag1 @etag2
                    Examples: First set of examples
                      | something | 
                      | here      |
                      
                    @etag2 @etag3
                    Examples: Second set of examples
                      | something | 
                      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Things are implemented' => true,
                ],
            ),
            '@feature-tag && @etag2',
        ];

        yield 'drops Outlines where no Examples match' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some work in progress
                
                  @scenario-tag
                  Scenario: Matches filter

                #  Scenario Outline: Things are implemented
                #    Given <something>
                #    
                #    @etag1
                #    Examples: First set of examples
                #      | something | 
                #      | here      |
                #      
                #    @etag1
                #    Examples: Second set of examples
                #      | something | 
                #      | else      |     
                GHERKIN,
                expectScenarioMatches: [
                    'Matches filter' => true,
                    'Things are implemented' => false,
                ],
            ),
            '@scenario-tag',
        ];

        yield 'matches all example tables across multiple Outlines' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some feature
                    
                  @wip
                  Scenario Outline: First scenario
                    Given <something>
                    
                    @etag1 @etag
                    Examples:
                      | something |
                      | here      |
                    
                    @etag2 @etag22 @etag
                    Examples: 
                      | something |
                      | else      |
                      
                  @wip
                  Scenario Outline: Second scenario
                    Given <other>
                    
                    @etag3 @etag22 @etag
                    Examples:   
                      | other |
                      | foo   | 
                      
                    @etag4 @etag
                    Examples: 
                      | other |
                      | bar   |
                GHERKIN,
                expectScenarioMatches: [
                    'First scenario' => true,
                    'Second scenario' => true,
                ],
            ),
            '@etag',
        ];

        yield 'matches only some example tables across multiple Outlines' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                @feature-tag
                Feature: Some feature
                    
                  @wip
                  Scenario Outline: First scenario
                    Given <something>
                    
                #    @etag1 @etag
                #    Examples:
                #      | something |
                #      | here      |
                    
                    @etag2 @etag22 @etag
                    Examples: 
                      | something |
                      | else      |
                      
                  @wip
                  Scenario Outline: Second scenario
                    Given <other>
                    
                    @etag3 @etag22 @etag
                    Examples:   
                      | other |
                      | foo   | 
                      
                #    @etag4 @etag
                #    Examples: 
                #      | other |
                #      | bar   |
                GHERKIN,
                expectScenarioMatches: [
                    'First scenario' => true,
                    'Second scenario' => true,
                ],
            ),
            '@etag22',
        ];
    }

    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, string $filterString): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new TagFilter($filterString));
    }

    /**
     * @return iterable<array{string, list<string>, bool}>
     */
    public static function providerFeatureMatches(): iterable
    {
        // Single tag matches if tag is present
        yield ['@wip', [], false];
        yield ['@wip', ['wip'], true];

        // Negated `~` tag matches if tag is NOT present
        yield ['~@done', ['wip'], true];
        yield ['~@done', ['wip', 'done'], false];

        // Or `,` matches if ANY of the list of tags is present
        yield ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3'], false];
        yield ['@tag5,@tag4,@tag6', ['tag1', 'tag2', 'tag3', 'tag5'], true];

        // And `&&` matches if ALL of the list of tags is present
        yield ['@wip&&@vip', ['wip', 'done'], false];
        yield ['@wip&&@vip', ['wip', 'done', 'vip'], true];

        // `,` has precedence over `&&` - resolves as "(@wip OR @vip) AND user"
        yield ['@wip,@vip&&@user', ['wip', 'user'], true];

        // `&&` with negated tag matches if positive tag is present AND negated tag is absent
        yield ['@wip&&~@slow', ['wip'], true];
        yield ['@wip&&~@slow', ['wip', 'slow'], false];
    }

    /**
     * @param list<string> $featureTags
     */
    #[DataProvider('providerFeatureMatches')]
    public function testIsFeatureMatchFilter(string $filterString, array $featureTags, bool $expect): void
    {
        $feature = new FeatureNode(null, null, $featureTags, null, [], '', '', null, 1);
        $filter = new TagFilter($filterString);
        $this->assertSame($expect, $filter->isFeatureMatch($feature));
    }

    public function testItFiltersAsExpectedIfChildClassModifiesFilterString(): void
    {
        $filter = new class('@wip') extends TagFilter {
            public function setFilterString(string $filterString): void
            {
                $this->filterString = $filterString;
            }
        };

        $feature = new FeatureNode(null, null, ['@wip'], null, [], '', '', null, 1);

        $this->assertTrue($filter->isFeatureMatch($feature), 'Matches initially');

        $filter->setFilterString('@wip&&@slow');

        $this->assertFalse($filter->isFeatureMatch($feature), 'Matches after filter is modified');
    }

    #[TestWith(['@bar', false])]
    #[TestWith(['@foo', true])]
    #[TestWith(['@outline-tag', true])]
    #[TestWith(['@foo && @outline-tag', true])]
    #[TestWith(['@foo && ~@outline-tag', false])]
    public function testItCanScenarioMatchAnExampleNode(string $filterString, bool $expect): void
    {
        // Although our own filtering logic never passes an ExampleNode, other callers might. For example Behat
        // does when checking if a Tagged Hook should be executed for a Scenario. Make sure that this is covered.
        // NB that the Scenario and Table tags are merged within OutlineNode::getExamples().
        $feature = $this->parseFeature(
            <<<'GHERKIN'
            Feature:

              @outline-tag
              Scenario: Something
                Given <something>
                
                @foo
                Examples:
                  | something |
                  | anything  | 
            GHERKIN,
        );
        $outline = $feature->getScenarios()[0];
        $this->assertInstanceOf(OutlineNode::class, $outline);
        $example = $outline->getExamples()[0];

        $tagFilter = new TagFilter($filterString);
        $this->assertSame($expect, $tagFilter->isScenarioMatch($feature, $example));
    }
}
