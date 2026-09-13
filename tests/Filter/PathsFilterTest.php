<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\PathsFilter;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\Attributes\DataProvider;

class PathsFilterTest extends FilterTestCase
{
    /**
     * @phpstan-return iterable<string,array{FeatureFilterTestFixture, string, list<string>}>
     */
    public static function providerFilterFeature(): iterable
    {
        yield 'matches whole feature if it is in an existing file in the search paths' => [
            FeatureFilterTestFixture::expectingNoFiltering(
                <<<'GHERKIN'
                Feature: Literally any feature
                  In order to do things with Behat
                  As a developer
                  I need to have feature files
                  
                  Scenario: Scenario 1             
                    
                  Scenario Outline: Outline 1
                    Given I <something>
                    
                    Examples:
                      | something |
                      | have      | 
                      | have not  | 
                GHERKIN,
                expectScenarioMatches: [
                    // Note, isScenarioMatch is always false for a PathsFilter
                    'Scenario 1' => false,
                    'Outline 1' => false,
                ],
            ),
            __FILE__,
            [__DIR__],
        ];

        yield 'filters to empty feature if the file is not in the search path' => [
            FeatureFilterTestFixture::fromCommentedExpectation(
                <<<'GHERKIN'
                Feature: Literally any feature
                  In order to do things with Behat
                  As a developer
                  I need to have feature files
                  
                #  Scenario: Scenario 1
                #    Given anything
                GHERKIN,
                expectScenarioMatches: [
                    'Scenario 1' => false,
                ],
            ),
            __FILE__,
            ['/some/other/path'],
        ];
    }

    /**
     * @param list<string> $filterPaths
     */
    #[DataProvider('providerFilterFeature')]
    public function testFilterFeature(FeatureFilterTestFixture $testcase, ?string $path, array $filterPaths): void
    {
        $this->assertFiltersFeatureAsExpected(
            $testcase,
            new PathsFilter($filterPaths),
            featureFilePath: $path,
        );
    }

    public function testIsFeatureMatchFilter(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], '', '', __FILE__, 1);

        $filter = new PathsFilter([__DIR__]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', dirname(__DIR__)]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', __DIR__]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', __DIR__, '/def']);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(['/abc', '/def', '/wrong/path']);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testItDoesNotMatchPartialPaths(): void
    {
        $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;

        $feature = new FeatureNode(null, null, [], null, [], '', '', $fixtures . 'full_path' . DIRECTORY_SEPARATOR . 'file1', 1);

        $filter = new PathsFilter([$fixtures . 'full']);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full' . DIRECTORY_SEPARATOR]);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full_path' . DIRECTORY_SEPARATOR]);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'full_path']);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter([$fixtures . 'ful._path']); // Don't accept regexp
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testItDoesNotMatchIfFileWithSameNameButNotPathExistsInFolder(): void
    {
        $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;

        $feature = new FeatureNode(null, null, [], null, [], '', '', $fixtures . 'full_path' . DIRECTORY_SEPARATOR . 'file1', 1);

        $filter = new PathsFilter([$fixtures . 'full']);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testMissingFileShouldBeSkipped(): void
    {
        $fixtures = __DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $filter = new PathsFilter([$fixtures . 'full']);

        $this->assertFalse($filter->isFeatureMatch($feature));
    }
}
