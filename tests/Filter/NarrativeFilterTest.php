<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\NarrativeFilter;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\Attributes\DataProvider;

class NarrativeFilterTest extends FilterTestCase
{
    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield 'matches title and description' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: Switch language to french
                  In order to be able to read news in my own language
                  As a french user
                  I need to be able to switch website language to french
                  
                  Scenario: Pick a language                
                    
                  Scenario Outline: Remember preferences
                    Given I <precondition> set my default language to french
                    When  I go to the site
                    Then  I should see the site in <language>
                    
                    Examples:
                      | precondition | language |
                      | have         | french   |
                      | have not     | english  |

                  Rule: Rule 1
                    Background:
                      Given rule background
                    
                    Scenario: Scenario 2
                      When something happens
                GHERKIN,
                expectScenarioMatches: [
                    // Note, isScenarioMatch is always false for a RoleFilter
                    'Pick a language' => false,
                    'Remember preferences' => false,
                    'Rule 1' => [
                        'Scenario 2' => false,
                    ],
                ],
            ),
            '/as (?:a|an) french user/i',
        ];

        yield 'filters to empty feature if it does not match' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Switch language to french
                  In order to be able to read news in my own language
                  As a french user
                  I need to be able to switch website language to french
                  
                #  Scenario: Pick a language                
                #    
                #  Scenario Outline: Remember preferences
                #    Given I <precondition> set my default language to french
                #    When  I go to the site
                #    Then  I should see the site in <language>
                #    
                #    Examples:
                #      | precondition | language |
                #      | have         | french   |
                #      | have not     | english  |
                #
                #  Rule: Rule 1
                #    Background:
                #      Given rule background
                #    
                #    Scenario: Scenario 2
                #      When something happens
                GHERKIN,
                expectScenarioMatches: [
                    'Pick a language' => false,
                    'Remember preferences' => false,
                    'Rule 1' => [
                        'Scenario 2' => false,
                    ],
                ],
            ),
            '/As (?:a|an) English user/',
        ];
    }

    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, string $filterString): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new NarrativeFilter($filterString));
    }

    public function testIsFeatureMatchFilter(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As a french user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new NarrativeFilter('/as (?:a|an) french user/');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/as (?:a|an) french user/i');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/french .*/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/^french/');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NarrativeFilter('/user$/');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
