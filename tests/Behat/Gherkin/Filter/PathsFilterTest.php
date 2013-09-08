<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\PathsFilter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;

require_once 'FilterTest.php';

class PathsFilterTest extends FilterTest
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, '/some/path/with/some.feature', 1);

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
        $scenario = new ScenarioNode(null, array(), array(), null, 2);
        $feature = new FeatureNode(null, null, array(), null, array($scenario), null, null, '/some/path/with/some.feature', 1);

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
