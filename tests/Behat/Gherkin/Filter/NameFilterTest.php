<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\NameFilter;

class NameFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new Node\FeatureNode();

        $feature->setTitle('random feature title');

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->setTitle('feature1');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTitle('feature1 title');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTitle('some feature1 title');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTitle('some feature title');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTitle('some feaSure title');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTitle('some feture title');
        $this->assertFalse($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $feature  = new Node\FeatureNode();
        $scenario = new Node\ScenarioNode();
        $feature->addScenario($scenario);

        $feature->setTitle('random feature title');
        $scenario->setTitle('UNKNOWN');

        $filter = new NameFilter('feature1');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->setTitle('feature1');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTitle('feature1 title');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTitle('some feature1 title');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTitle('some feature title');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/fea.ure/');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTitle('some feaSure title');
        $scenario->setTitle('some feature title');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTitle('some feture title');
        $scenario->setTitle('unk');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->setTitle('unknown');
        $scenario->setTitle('simple scenario title');
        $filter = new NameFilter('scenario');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario->setTitle('simple feature title');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario->setTitle('simple scenerio title');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new NameFilter('/scen.rio/');
        $this->assertTrue($filter->isScenarioMatch($scenario));
    }
}
