<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\OutlineNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Node\FeatureNode;

class OutlineNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testTitle()
    {
        $outline = new OutlineNode();
        $this->assertNull($outline->getTitle());

        $outline->setTitle('test title 1');
        $this->assertEquals('test title 1', $outline->getTitle());

        $outline = new OutlineNode('test title 2');
        $this->assertEquals('test title 2', $outline->getTitle());
    }

    public function testLine()
    {
        $outline = new OutlineNode();
        $this->assertEquals(0, $outline->getLine());

        $outline = new OutlineNode(null, 23);
        $this->assertEquals(23, $outline->getLine());
    }

    public function testExamples()
    {
        $outline = new OutlineNode();
        $this->assertNull($outline->getExamples());
        $this->assertFalse($outline->hasExamples());

        $outline->setExamples($table = new TableNode());
        $this->assertSame($table, $outline->getExamples());
        $this->assertTrue($outline->hasExamples());
    }

    public function testSteps()
    {
        $outline = new OutlineNode();
        $this->assertEquals(0, count($outline->getSteps()));
        $this->assertFalse($outline->hasSteps());

        $outline->addStep(new StepNode('Given', 'Something'));
        $this->assertEquals(1, count($outline->getSteps()));
        $this->assertTrue($outline->hasSteps());

        $outline->addStep(new StepNode('Then', 'Do'));
        $this->assertEquals(2, count($outline->getSteps()));
        $this->assertTrue($outline->hasSteps());

        $steps = $outline->getSteps();
        $this->assertInstanceOf('Behat\Gherkin\Node\StepNode', $steps[0]);

        $this->assertEquals('Given', $steps[0]->getType());
        $this->assertEquals('Something', $steps[0]->getText());
        $this->assertSame($outline, $steps[0]->getParent());

        $this->assertEquals('Then', $steps[1]->getType());
        $this->assertEquals('Do', $steps[1]->getText());
        $this->assertSame($outline, $steps[1]->getParent());
    }

    public function testFeature()
    {
        $outline = new OutlineNode();
        $this->assertNull($outline->getFeature());

        $outline->setFeature($feature = new FeatureNode());
        $this->assertSame($feature, $outline->getFeature());
    }

    public function testTags()
    {
        $outline = new OutlineNode();
        $this->assertFalse($outline->hasTags());
        $this->assertInternalType('array', $outline->getTags());
        $this->assertEquals(0, count($outline->getTags()));

        $outline->setTags($tags = array('tag1', 'tag2'));
        $this->assertEquals($tags, $outline->getTags());

        $outline->addTag('tag3');
        $this->assertEquals(array('tag1', 'tag2', 'tag3'), $outline->getTags());

        $this->assertFalse($outline->hasTag('tag4'));
        $this->assertTrue($outline->hasTag('tag2'));
        $this->assertTrue($outline->hasTag('tag3'));
    }
}
