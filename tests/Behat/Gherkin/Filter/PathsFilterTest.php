<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\PathsFilter;

class PathsFilterTest extends FilterTest
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new Node\FeatureNode(null, null, '/some/path/with/some.feature', 1);

        $filter = new PathsFilter(array('/some'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', '/some'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', '/some/path'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/some/path', '/def'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new PathsFilter(array('/abc', '/def', '/wrong/path'));
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $feature = new Node\FeatureNode(null, null, '/some/path/with/some.feature', 1);

        $scenario = new Node\ScenarioNode(null, 2);
        $scenario->setFeature($feature);

        $filter = new PathsFilter(array('/some'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PathsFilter(array('/abc', '/def', '/some'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PathsFilter(array('/abc', '/def', '/some/path'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PathsFilter(array('/abc', '/some/path', '/def'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PathsFilter(array('/abc', '/def', '/wrong/path'));
        $this->assertFalse($filter->isScenarioMatch($scenario));
    }
}
