<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\LineFilter;

class LineFilterTest extends FilterTest
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new Node\FeatureNode(null, null, null, 1);

        $filter = new LineFilter(1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new LineFilter(2);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new LineFilter(3);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $scenario = new Node\ScenarioNode(null, 2);

        $filter = new LineFilter(2);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new LineFilter(1);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new LineFilter(5);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $outline = new Node\OutlineNode(null, 20);

        $filter = new LineFilter(5);
        $this->assertFalse($filter->isScenarioMatch($outline));

        $filter = new LineFilter(20);
        $this->assertTrue($filter->isScenarioMatch($outline));
    }

    public function testFilterFeatureScenario()
    {
        $filter = new LineFilter(2);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#1', $scenarios[0]->getTitle());

        $filter = new LineFilter(7);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#2', $scenarios[0]->getTitle());

        $filter = new LineFilter(5);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(0, $scenarios = $feature->getScenarios());
    }

    public function testFilterFeatureOutline()
    {
        $filter = new LineFilter(13);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(4, $scenarios[0]->getExamples()->getRows());

        $filter = new LineFilter(19);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(2, $scenarios[0]->getExamples()->getRows());
        $this->assertSame(array(
            array('action', 'outcome'),
            array('act#1', 'out#1'),
        ), $scenarios[0]->getExamples()->getRows());

        $filter = new LineFilter(21);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(2, $scenarios[0]->getExamples()->getRows());
        $this->assertSame(array(
            array('action', 'outcome'),
            array('act#3', 'out#3'),
        ), $scenarios[0]->getExamples()->getRows());

        $filter = new LineFilter(18);
        $filter->filterFeature($feature = $this->getParsedFeature());
        $this->assertCount(1, $scenarios = $feature->getScenarios());
        $this->assertSame('Scenario#3', $scenarios[0]->getTitle());
        $this->assertCount(1, $scenarios[0]->getExamples()->getRows());
        $this->assertSame(array(
            array('action', 'outcome'),
        ), $scenarios[0]->getExamples()->getRows());
    }
}
