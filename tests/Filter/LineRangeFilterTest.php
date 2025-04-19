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
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
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
     * @return iterable<array{numeric-string, numeric-string|'*', int}>
     */
    public static function scenarioLineRangeProvider(): iterable
    {
        return [
            ['1', '2', 1],
            ['1', '*', 2],
            ['2', '2', 1],
            ['2', '*', 2],
            ['3', '3', 1],
            ['3', '*', 1],
            ['1', '1', 0],
            ['4', '4', 0],
            ['4', '*', 0],
        ];
    }

    /**
     * @param numeric-string $filterMinLine
     * @param numeric-string|'*' $filterMaxLine
     */
    #[DataProvider('scenarioLineRangeProvider')]
    public function testIsScenarioMatchFilter(string $filterMinLine, string $filterMaxLine, int $expectedNumberOfMatches): void
    {
        $scenario = new ScenarioNode(null, [], [], '', 2);
        $outline = new OutlineNode(null, [], [], [new ExampleTableNode([], '')], '', 3);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $this->assertEquals(
            $expectedNumberOfMatches,
            (int) $filter->isScenarioMatch($scenario) + (int) $filter->isScenarioMatch($outline)
        );
    }

    public function testFilterFeatureScenario(): void
    {
        $filter = new LineRangeFilter(1, 3);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#1', $scenarios[0]->getTitle());

        $filter = new LineRangeFilter(5, 9);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#2', $scenarios[0]->getTitle());

        $filter = new LineRangeFilter(5, 6);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(0, $scenarios = $feature->getScenarios());
    }

    public function testFilterFeatureOutline(): void
    {
        $filter = new LineRangeFilter(12, 14);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertInstanceOf(OutlineNode::class, $scenarios[0]);
        $this->assertFalse($scenarios[0]->hasExamples());

        $filter = new LineRangeFilter(16, 21);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertInstanceOf(OutlineNode::class, $scenarios[0]);
        $exampleTableNodes = $scenarios[0]->getExampleTables();
        $this->assertCount(1, $exampleTableNodes);
        $this->assertCount(3, $exampleTableNodes[0]->getRows());
        $this->assertSame([
            ['action', 'outcome'],
            ['act#1', 'out#1'],
            ['act#2', 'out#2'],
        ], $exampleTableNodes[0]->getRows());
        $this->assertEquals(['etag1'], $exampleTableNodes[0]->getTags());

        $filter = new LineRangeFilter(16, 26);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertInstanceOf(OutlineNode::class, $scenarios[0]);
        $exampleTableNodes = $scenarios[0]->getExampleTables();
        $this->assertCount(2, $exampleTableNodes);

        $this->assertCount(3, $exampleTableNodes[0]->getRows());
        $this->assertSame([
            ['action', 'outcome'],
            ['act#1', 'out#1'],
            ['act#2', 'out#2'],
        ], $exampleTableNodes[0]->getRows());
        $this->assertEquals(['etag1'], $exampleTableNodes[0]->getTags());

        $this->assertCount(2, $exampleTableNodes[1]->getRows());
        $this->assertSame([
            ['action', 'outcome'],
            ['act#3', 'out#3'],
        ], $exampleTableNodes[1]->getRows());

        $this->assertEquals(['etag2'], $exampleTableNodes[1]->getTags());

        $filter = new LineRangeFilter(25, 26);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertInstanceOf(OutlineNode::class, $scenarios[0]);
        $exampleTableNodes = $scenarios[0]->getExampleTables();
        $this->assertCount(1, $exampleTableNodes);
        $this->assertCount(2, $exampleTableNodes[0]->getRows());
        $this->assertSame([
            ['action', 'outcome'],
            ['act#3', 'out#3'],
        ], $exampleTableNodes[0]->getRows());
        $this->assertEquals(['etag2'], $exampleTableNodes[0]->getTags());
    }
}
