<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\LineRangeFilter;

class LineRangeFilterTest extends FilterTest
{
    public function featureLineRangeProvider()
    {
        return array(
            array('1', '1', true),
            array('1', '2', true),
            array('1', '*', true),
            array('2', '2', false),
            array('2', '*', false)
        );
    }

    /**
     * @dataProvider featureLineRangeProvider
     */
    public function testIsFeatureMatchFilter($filterMinLine, $filterMaxLine, $expected)
    {
        $feature = new Node\FeatureNode(null, null, null, 1);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $this->assertSame($expected, $filter->isFeatureMatch($feature));
    }

    public function scenarioLineRangeProvider()
    {
        return array(
            array('1', '2', 1),
            array('1', '*', 2),
            array('2', '2', 1),
            array('2', '*', 2),
            array('3', '3', 1),
            array('3', '*', 1),
            array('1', '1', 0),
            array('4', '4', 0),
            array('4', '*', 0)
        );
    }

    /**
     * @dataProvider scenarioLineRangeProvider
     */
    public function testIsScenarioMatchFilter($filterMinLine, $filterMaxLine, $expectedNumberOfMatches)
    {
        $scenario = new Node\ScenarioNode(null, 2);
        $outline = new Node\OutlineNode(null, 3);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $this->assertEquals($expectedNumberOfMatches, intval($filter->isScenarioMatch($scenario))
            + intval($filter->isScenarioMatch($outline)));
    }

    public function testFilterFeatureScenario()
    {
        $filter = new LineRangeFilter(1, 3);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#1', $scenarios[0]->getTitle());

        $filter = new LineRangeFilter(5, 9);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#2', $scenarios[0]->getTitle());

        $filter = new LineRangeFilter(5, 6);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(0, $scenarios = $feature->getScenarios());
    }

    public function testFilterFeatureOutline()
    {
        $filter = new LineRangeFilter(12, 14);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(1, $scenarios[0]->getExamples()->getRows());

        $filter = new LineRangeFilter(15, 20);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(3, $scenarios[0]->getExamples()->getRows());
        $this->assertSame(array(
            array('action', 'outcome'),
            array('act#1', 'out#1'),
            array('act#2', 'out#2'),
        ), $scenarios[0]->getExamples()->getRows());
    }
}
