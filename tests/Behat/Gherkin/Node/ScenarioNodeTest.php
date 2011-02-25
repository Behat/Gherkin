<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\FeatureNode;

class ScenarioNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testTitle()
    {
        $scenario = new ScenarioNode();
        $this->assertNull($scenario->getTitle());

        $scenario->setTitle('test title 1');
        $this->assertEquals('test title 1', $scenario->getTitle());

        $scenario = new ScenarioNode('test title 2');
        $this->assertEquals('test title 2', $scenario->getTitle());
    }

    public function testLine()
    {
        $scenario = new ScenarioNode();
        $this->assertEquals(0, $scenario->getLine());

        $scenario = new ScenarioNode(null, 23);
        $this->assertEquals(23, $scenario->getLine());
    }

    public function testSteps()
    {
        $scenario = new ScenarioNode();
        $this->assertEquals(0, count($scenario->getSteps()));
        $this->assertFalse($scenario->hasSteps());

        $scenario->addStep(new StepNode('Given', 'Something'));
        $this->assertEquals(1, count($scenario->getSteps()));
        $this->assertTrue($scenario->hasSteps());

        $scenario->addStep(new StepNode('Then', 'Do'));
        $this->assertEquals(2, count($scenario->getSteps()));
        $this->assertTrue($scenario->hasSteps());

        $steps = $scenario->getSteps();
        $this->assertInstanceOf('Behat\Gherkin\Node\StepNode', $steps[0]);

        $this->assertEquals('Given', $steps[0]->getType());
        $this->assertEquals('Something', $steps[0]->getText());
        $this->assertSame($scenario, $steps[0]->getParent());

        $this->assertEquals('Then', $steps[1]->getType());
        $this->assertEquals('Do', $steps[1]->getText());
        $this->assertSame($scenario, $steps[1]->getParent());
    }

    public function testFeature()
    {
        $scenario = new ScenarioNode();
        $this->assertNull($scenario->getFeature());

        $scenario->setFeature($feature = new FeatureNode());
        $this->assertSame($feature, $scenario->getFeature());
    }

    public function testTags()
    {
        $scenario = new ScenarioNode();
        $this->assertFalse($scenario->hasTags());
        $this->assertInternalType('array', $scenario->getTags());
        $this->assertEquals(0, count($scenario->getTags()));

        $scenario->setTags($tags = array('tag1', 'tag2'));
        $this->assertEquals($tags, $scenario->getTags());

        $scenario->addTag('tag3');
        $this->assertEquals(array('tag1', 'tag2', 'tag3'), $scenario->getTags());

        $this->assertFalse($scenario->hasTag('tag4'));
        $this->assertTrue($scenario->hasTag('tag2'));
        $this->assertTrue($scenario->hasTag('tag3'));
    }
}
