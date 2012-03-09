<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\LineRangeFilter;

class LineRangeFilterTest extends \PHPUnit_Framework_TestCase
{
    public function featureLineRangeProvider()
    {
        return array(
            array('1', '1'),
            array('1', '2'),
            array('1', '*'),
            array('2', '2'),
            array('2', '*')
        );
    }

    /**
     * @dataProvider featureLineRangeProvider
     */
    public function testIsFeatureMatchFilter($filterMinLine, $filterMaxLine)
    {
        $feature = new Node\FeatureNode(null, null, null, 1);

        $filter = new LineRangeFilter($filterMinLine, $filterMaxLine);
        $this->assertTrue($filter->isFeatureMatch($feature));
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
}
