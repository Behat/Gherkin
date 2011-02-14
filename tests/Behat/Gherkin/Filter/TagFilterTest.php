<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Node,
    Behat\Gherkin\Filter\TagFilter;

class TagFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsFeatureMatchFilter()
    {
        $feature = new Node\FeatureNode();

        $filter  = new TagFilter('@wip');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->addTag('wip');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter  = new TagFilter('~@done');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->addTag('done');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->setTags(array('tag1', 'tag2', 'tag3'));
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->addTag('tag5');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip&&@vip');
        $feature->setTags(array('wip', 'not-done'));
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->addTag('vip');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip,@vip&&@user');
        $feature->setTags(array('wip'));
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->setTags(array('vip'));
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature->setTags(array('wip', 'user'));
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature->setTags(array('vip', 'user'));
        $this->assertTrue($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $feature    = new Node\FeatureNode();
        $scenario   = new Node\ScenarioNode();
        $feature->addScenario($scenario);

        $filter  = new TagFilter('@wip');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('wip');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter  = new TagFilter('~@done');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->addTag('done');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->setTags(array('tag1', 'tag2', 'tag3'));
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('tag5');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new TagFilter('@wip&&@vip');
        $feature->setTags(array('wip', 'not-done'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('vip');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new TagFilter('@wip,@vip&&@user');
        $feature->setTags(array('wip'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->setTags(array('vip'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->setTags(array('wip', 'user'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTags(array('vip', 'user'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTags(array());

        $filter  = new TagFilter('@wip');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('wip');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter  = new TagFilter('~@done');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->addTag('done');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario->setTags(array('tag1', 'tag2', 'tag3'));
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('tag5');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new TagFilter('@wip&&@vip');
        $scenario->setTags(array('wip', 'not-done'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $feature->addTag('vip');
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $filter = new TagFilter('@wip,@vip&&@user');
        $scenario->setTags(array('wip'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario->setTags(array('vip'));
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $scenario->setTags(array('wip', 'user'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario->setTags(array('vip', 'user'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTags(array('wip'));
        $scenario->setTags(array('user'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $feature->setTags(array());
        $this->assertFalse($filter->isScenarioMatch($scenario));

        $filter = new TagFilter('@wip,@vip&&~@group');
        $feature->setTags(array('vip'));
        $this->assertTrue($filter->isScenarioMatch($scenario));

        $scenario->addTag('group');
        $this->assertFalse($filter->isScenarioMatch($scenario));
    }
}
