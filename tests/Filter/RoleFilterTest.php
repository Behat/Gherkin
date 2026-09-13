<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\RoleFilter;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\Attributes\DataProvider;

class RoleFilterTest extends FilterTestCase
{
    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield 'matches whole feature if anything matches' => [
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
                GHERKIN,
                expectScenarioMatches: [
                    // Note, isScenarioMatch is always false for a RoleFilter
                    'Pick a language' => false,
                    'Remember preferences' => false,
                ],
            ),
            'french user',
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
                GHERKIN,
                expectScenarioMatches: [
                    'Pick a language' => false,
                    'Remember preferences' => false,
                ],
            ),
            'german user',
        ];
    }

    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, string $filterString): void
    {
        $this->assertFiltersFeatureAsExpected($testcase, new RoleFilter($filterString));
    }

    public function testIsFeatureMatchFilter(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As a french user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new RoleFilter('french user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('french *');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('french');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('*user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('French User');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new RoleFilter('French User');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testFeatureRolePrefixedWithAn(): void
    {
        $description = <<<'NAR'
        In order to be able to read news in my own language
        As an american user
        I need to be able to switch website language to french
        NAR;
        $feature = new FeatureNode(null, $description, [], null, [], '', '', null, 1);

        $filter = new RoleFilter('american user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('american *');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('american');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('*user');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('[\w\s]+user');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new RoleFilter('American User');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);
        $filter = new RoleFilter('American User');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
