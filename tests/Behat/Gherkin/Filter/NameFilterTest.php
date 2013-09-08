<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\NameFilter;
use Behat\Gherkin\Node;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;

class NameFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new FeatureNode('random feature title', null, array(), null, array(), null, null, null, 1);

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1', null, array(), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('feature1 title', null, array(), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature1 title', null, array(), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feature title', null, array(), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feaSure title', null, array(), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode('some feture title', null, array(), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $scenario = new ScenarioNode('UNKNOWN', array(), array(), null, 2);
        $feature = new FeatureNode('random feature title', null, array(), null, array($scenario), null, null, null, 1);

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature = new FeatureNode('feature1', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature = new FeatureNode('feature1 title', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature = new FeatureNode('some feature1 title', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature = new FeatureNode('some feature title', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('some feature title', array(), array(), null, 2);
        $feature = new FeatureNode('some feaSure title', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('unk', array(), array(), null, 2);
        $feature = new FeatureNode('some feture title', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('simple scenario title', array(), array(), null, 2);
        $feature = new FeatureNode('unknown', null, array(), null, array($scenario), null, null, null, 1);
        $filter = new NameFilter('scenario');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('simple feature title', array(), array(), null, 2);
        $feature = new FeatureNode('unknown', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario = new ScenarioNode('simple scenerio title', array(), array(), null, 2);
        $feature = new FeatureNode('unknown', null, array(), null, array($scenario), null, null, null, 1);
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/scen.rio/');
        $this->assertTrue($filter->isScenarioMatch($scenario));
    }
}
