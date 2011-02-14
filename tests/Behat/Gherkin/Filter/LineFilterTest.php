<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\LineFilter;

class LineFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new Node\FeatureNode(null, null, null, 1);

        $filter = new LineFilter(1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new LineFilter(2);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new LineFilter(3);
        $this->assertTrue($filter->isFeatureMatch($feature));
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
}
