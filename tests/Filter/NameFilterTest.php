<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\Attributes\DataProvider;

class NameFilterTest extends FilterTestCase
{
    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield from self::providerFilterFeatureOnFeatureTitle();
        yield from self::providerFilterFeatureOnScenarioTitleAndDescription();
        yield from self::providerFilterFeatureOnRuleTitle();
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    private static function providerFilterFeatureOnFeatureTitle(): iterable
    {
        yield 'matches feature title' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                  Scenario: Open a new tab
                    The session will still be available
                    
                    When I do something
                    Then things should happen
                    
                  Scenario Outline: Open a new window
                    The session will not be available
                    
                    When I do <something>
                    Then things should happen
                    
                    Examples:
                      | something |
                      | click     |
                      | type      |

                  Rule: Some rule
                    Background:
                      Given rule background

                    Scenario: Scenario inside rule
                      Given something  
                GHERKIN,
                expectScenarioMatches: [
                    // NOTE: isScenarioMatch does NOT consider the feature or rule text
                    'Open a new tab' => false,
                    'Open a new window' => false,
                    'Some rule' => [
                        'Scenario inside rule' => false,
                    ],
                ],
            ),
            'browser',
        ];

        yield 'feature title does not match' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                #  Scenario: Open a new tab
                #    The session will still be available
                #    
                #    When I do something
                #    Then things should happen
                #    
                #  Scenario Outline: Open a new window
                #    The session will not be available
                #    
                #    When I do <something>
                #    Then things should happen
                #    
                #    Examples:
                #      | something |
                #      | click     |
                #      | type      |
                #
                #  Rule: Some rule
                #    Background:
                #      Given rule background
                #
                #    Scenario: Scenario inside rule
                #      Given something  
                GHERKIN,
                expectScenarioMatches: [
                    // NOTE: isScenarioMatch does NOT consider the feature or rule text
                    'Open a new tab' => false,
                    'Open a new window' => false,
                    'Some rule' => [
                        'Scenario inside rule' => false,
                    ],
                ],
            ),
            'calculator',
        ];

        yield 'matches feature title by regex' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                  Scenario: Open a new tab
                    The session will still be available
                    
                    When I do something
                    Then things should happen
                    
                  Scenario Outline: Open a new window
                    The session will not be available
                    
                    When I do <something>
                    Then things should happen
                    
                    Examples:
                      | something |
                      | click     |
                      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    // NOTE: isScenarioMatch does NOT consider the feature text
                    'Open a new tab' => false,
                    'Open a new window' => false,
                ],
            ),
            '/^Some browser [f]/',
        ];

        yield 'does not match feature title by regex' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                #  Scenario: Open a new tab
                #    The session will still be available
                #    
                #    When I do something
                #    Then things should happen
                #    
                #  Scenario Outline: Open a new window
                #    The session will not be available
                #    
                #    When I do <something>
                #    Then things should happen
                #    
                #    Examples:
                #      | something |
                #      | click     |
                #      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    // NOTE: isScenarioMatch does NOT consider the feature text
                    'Open a new tab' => false,
                    'Open a new window' => false,
                ],
            ),
            '/^In my browser [f]/',
        ];

        yield 'ignores feature description' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                #  Scenario: Open a new tab
                #    The session will still be available
                #    
                #    When I do something
                #    Then things should happen
                #    
                #  Scenario Outline: Open a new window
                #    The session will not be available
                #    
                #    When I do <something>
                #    Then things should happen
                #    
                #    Examples:
                #      | something |
                #      | click     |
                #      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    // NOTE: isScenarioMatch does NOT consider the feature text
                    'Open a new tab' => false,
                    'Open a new window' => false,
                ],
            ),
            'things happening in the browser',
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    private static function providerFilterFeatureOnScenarioTitleAndDescription(): iterable
    {
        yield 'matches scenario title' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                  Scenario: Open a new tab
                    The session will still be available
                    
                    When I do something
                    Then things should happen
                    
                #  Scenario Outline: Open a new window
                #    The session will not be available
                #    
                #    When I do <something>
                #    Then things should happen
                #    
                #    Examples:
                #      | something |
                #      | click     |
                #      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    'Open a new tab' => true,
                    'Open a new window' => false,
                ],
            ),
            'new tab',
        ];

        yield 'matches scenario description' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                  Scenario: Open a new tab
                    The session will still be available
                    
                    When I do something
                    Then things should happen
                    
                #  Scenario Outline: Open a new window
                #    The session will not be available
                #    
                #    When I do <something>
                #    Then things should happen
                #    
                #    Examples:
                #      | something |
                #      | click     |
                #      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    'Open a new tab' => true,
                    'Open a new window' => false,
                ],
            ),
            'session will still be',
        ];

        yield 'matches outline title' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                #  Scenario: Open a new tab
                #    The session will still be available
                #    
                #    When I do something
                #    Then things should happen
                    
                  Scenario Outline: Open a new window
                    The session will not be available
                    
                    When I do <something>
                    Then things should happen
                    
                    Examples:
                      | something |
                      | click     |
                      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    'Open a new tab' => false,
                    'Open a new window' => true,
                ],
            ),
            'new window',
        ];

        yield 'matches outline description' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Some browser feature
                  That describes things happening in the browser
                  
                #  Scenario: Open a new tab
                #    The session will still be available
                #    
                #    When I do something
                #    Then things should happen
                    
                  Scenario Outline: Open a new window
                    The session will not be available
                    
                    When I do <something>
                    Then things should happen
                    
                    Examples:
                      | something |
                      | click     |
                      | type      |
                GHERKIN,
                expectScenarioMatches: [
                    'Open a new tab' => false,
                    'Open a new window' => true,
                ],
            ),
            'session will not be',
        ];

        yield 'does not match untitled scenario' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something
                 
                #  Scenario:
                #    When I do things            
                GHERKIN,
                expectScenarioMatches: [
                    '' => false,
                ],
            ),
            'anything',
        ];

        yield 'matches scenario with title only' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: Something
                 
                  Scenario: Prove a requirement
                    When I do things            
                GHERKIN,
                expectScenarioMatches: [
                    'Prove a requirement' => true,
                ],
            ),
            'requirement',
        ];

        yield 'matches on scenario title inside Rule' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something

                   Rule: Rule 1
                     Background:
                       Given rule background
                 
                     Scenario: Browser things in rule 1
                       Given something
                 
                #    Scenario: CLI things in rule 1
                #      Given something
                       
                   Rule: Rule 2
                     Background:
                       Given rule background
                 
                     Scenario: Browser things in rule 2
                       Given something
                 
                #     Scenario: CLI things in rule 2
                #       Given something
                GHERKIN,
                [
                    'Rule 1' => [
                        'Browser things in rule 1' => true,
                        'CLI things in rule 1' => false,
                    ],
                    'Rule 2' => [
                        'Browser things in rule 2' => true,
                        'CLI things in rule 2' => false,
                    ],
                ]
            ),
            '/^browser/i',
        ];

        yield 'drops Rules that contain no matching scenarios' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something

                #   Rule: Rule 1
                #     Background:
                #       Given rule background
                # 
                #     Scenario: App things in rule 1
                #       Given something
                # 
                #    Scenario: CLI things in rule 1
                #      Given something
                       
                   Rule: Rule 2
                     Background:
                       Given rule background
                 
                     Scenario: Browser things in rule 2
                       Given something
                 
                #     Scenario: CLI things in rule 2
                #       Given something
                GHERKIN,
                [
                    'Rule 1' => [
                        'App things in rule 1' => false,
                        'CLI things in rule 1' => false,
                    ],
                    'Rule 2' => [
                        'Browser things in rule 2' => true,
                        'CLI things in rule 2' => false,
                    ],
                ]
            ),
            '/^browser/i',
        ];
    }

    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    private static function providerFilterFeatureOnRuleTitle(): iterable
    {
        yield 'does not match untitled rule' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something
                 
                #  Rule:  
                #    Scenario: Whatever
                #      When I do things            
                GHERKIN,
                expectScenarioMatches: [
                    '' => [
                        'Whatever' => false,
                    ],
                ],
            ),
            'anything',
        ];

        yield 'matches all scenarios inside Rule with title that contains name string' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something

                   Rule: The first rule
                     Background:
                       Given rule background
                 
                     Scenario: Browser things in rule 1
                       Given something
                 
                     Scenario: CLI things in rule 1
                       Given something
                       
                #  Rule: The second rule
                #    Background:
                #      Given rule background
                #
                #    Scenario: Browser things in rule 2
                #      Given something
                #
                #     Scenario: CLI things in rule 2
                #       Given something
                GHERKIN,
                [
                    'The first rule' => [
                        // NOTE: These scenarios are *included* by `filterFeature` but do *not* pass `isScenarioMatch`
                        // This is consistent with legacy behaviour where `isScenarioMatch` only considers the scenario
                        // text, even though `filterFeature` also matches on the feature title.
                        'Browser things in rule 1' => false,
                        'CLI things in rule 1' => false,
                    ],
                    'The second rule' => [
                        'Browser things in rule 2' => false,
                        'CLI things in rule 2' => false,
                    ],
                ]
            ),
            'first',
        ];

        yield 'matches all scenarios inside Rule with title that matches name regex' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something

                #   Rule: The first rule
                #     Background:
                #       Given rule background
                # 
                #     Scenario: Browser things in rule 1
                #       Given something
                # 
                #     Scenario: CLI things in rule 1
                #       Given something
                       
                  Rule: The second rule
                    Background:
                      Given rule background
                
                    Scenario: Browser things in rule 2
                      Given something
                
                     Scenario: CLI things in rule 2
                       Given something
                GHERKIN,
                [
                    'The first rule' => [
                        'Browser things in rule 1' => false,
                        'CLI things in rule 1' => false,
                    ],
                    'The second rule' => [
                        // NOTE: These scenarios are *included* by `filterFeature` but do *not* pass `isScenarioMatch`
                        // This is consistent with legacy behaviour where `isScenarioMatch` only considers the scenario
                        // text, even though `filterFeature` also matches on the feature title.
                        'Browser things in rule 2' => false,
                        'CLI things in rule 2' => false,
                    ],
                ]
            ),
            '/^The Second/i',
        ];

        yield 'does NOT match on Rule description' => [
            // There's a choice on how to handle this:
            // - Feature only matches on the title
            // - Scenario matches on title and description - but only for BC, because the description used to be parsed
            //   into a multiline title.
            // IMO it is more predictable / consistent with the meaning of `name` to match on the title only.
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Something

                   Rule: When the account has money
                     This one matches because the key word is in the title
                
                     Background:
                       Given rule background
                 
                     Scenario: Withdraw cash in rule 1
                       Given something
                       
                #  Rule: When they are rich
                #    They have money, but we only said that in the description
                #    
                #    Background:
                #      Given rule background
                #
                #     Scenario: Withdraw cash in rule 2
                #       Given something
                GHERKIN,
                [
                    'When the account has money' => [
                        // NOTE: This scenario is *included* by `filterFeature` but do *not* pass `isScenarioMatch`
                        // This is consistent with legacy behaviour where `isScenarioMatch` only considers the scenario
                        // text, even though `filterFeature` also matches on the feature title.
                        'Withdraw cash in rule 1' => false,
                    ],
                    'When they are rich' => [
                        'Withdraw cash in rule 2' => false,
                    ],
                ]
            ),
            'money',
        ];
    }

    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, string $filterString): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new NameFilter($filterString));
    }

    public function testIsFeatureMatchFilter(): void
    {
        $feature = new FeatureNode('random feature title', null, [], null, [], '', '', null, 1);

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1 title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature1 title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature title', null, [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feaSure title', null, [], null, [], '', '', null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feture title', null, [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testFeatureFilterDoesNotMatchDescription(): void
    {
        // Feature descriptions are parsed separately for all GherkinCompatibilityMode settings, and have always been.
        // So for BC we ignore them when filtering a feature.
        $filter = new NameFilter('feature1');
        $feature = new FeatureNode('some feature title', 'for feature1', [], null, [], '', '', null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testUntitledFeatureDoesNotMatch(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new NameFilter('');

        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter(): void
    {
        $filter = new NameFilter('scenario1');

        $scenario = new ScenarioNode('UNKNOWN', [], [], '', 2);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('scenario1', [], [], '', 2);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('scenario1 title', [], [], '', 2);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('some scenario title', [], [], '', 2);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/sce.ario/');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/scen.rio/');
        $this->assertTrue($filter->isScenarioMatch($scenario));
    }

    /**
     * @phpstan-return array<string, list{array{title: string, description: string|null}, bool}>
     */
    public static function providerScenarioMatchDescription(): array
    {
        return [
            'parsed in legacy mode, not matching filter' => [
                ['title' => "multiline\nwith start in text", 'description' => ''],
                false,
            ],
            'parsed in legacy mode, matching filter' => [
                ['title' => "multiline\nstarting as expected", 'description' => ''],
                true,
            ],
            'parsed in compat mode, not matching filter' => [
                ['title' => 'multiline', 'description' => 'with start in text'],
                false,
            ],
            'parsed in compat mode, matching filter' => [
                ['title' => 'multiline', 'description' => 'starting as expected'],
                true,
            ],
            'parsed in compat mode, title matches (and no description)' => [
                ['title' => 'starting title', 'description' => null],
                true,
            ],
        ];
    }

    /**
     * @param array{title:string|null, description:string|null} $scenario
     */
    #[DataProvider('providerScenarioMatchDescription')]
    public function testScenarioFilterMatchesIncludingDescription(array $scenario, bool $expectMatch): void
    {
        // Scenarios may be parsed with multi-line text titles, or with a single line title followed by a description,
        // depending on the GherkinCompatibilityMode.
        // So for BC, the filter considers title *and* description when matching by name.
        $filter = new NameFilter('/^start/m');
        $scenario = new ScenarioNode($scenario['title'], [], [], '', 2, $scenario['description']);
        $this->assertSame($expectMatch, $filter->isScenarioMatch($scenario));
    }

    /**
     * @phpstan-return array<string, list<ScenarioInterface>>
     */
    public static function providerScenarioFilterValidTypes(): array
    {
        return [
            'ScenarioNode' => [new ScenarioNode('Scenario match', [], [], '', 2)],
            'OutlineNode' => [new OutlineNode('Outline match', [], [], [], '', 2)],
            // ExampleNode is an example of a ScenarioInterface that does *not* have a description property
            'ExampleNode' => [new ExampleNode('Example match', [], [], [], 2, '', 1)],
        ];
    }

    #[DataProvider('providerScenarioFilterValidTypes')]
    public function testScenarioFilterMatchesAllScenarioInterface(ScenarioInterface $scenario): void
    {
        $filter = new NameFilter('match');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('no match');
        $this->assertFalse($filter->isScenarioMatch($scenario));
    }
}
