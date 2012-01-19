<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\FeatureNode;

class BackgroundNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testLine()
    {
        $background = new BackgroundNode();
        $this->assertEquals(0, $background->getLine());

        $background = new BackgroundNode(null, 23);
        $this->assertEquals(23, $background->getLine());
    }

    public function testSteps()
    {
        $background = new BackgroundNode();
        $this->assertEquals(0, count($background->getSteps()));
        $this->assertFalse($background->hasSteps());

        $background->addStep(new StepNode('Given', 'Something'));
        $this->assertEquals(1, count($background->getSteps()));
        $this->assertTrue($background->hasSteps());

        $background->addStep(new StepNode('Then', 'Do'));
        $this->assertEquals(2, count($background->getSteps()));
        $this->assertTrue($background->hasSteps());

        $steps = $background->getSteps();
        $this->assertInstanceOf('Behat\Gherkin\Node\StepNode', $steps[0]);

        $this->assertEquals('Given', $steps[0]->getType());
        $this->assertEquals('Something', $steps[0]->getText());
        $this->assertSame($background, $steps[0]->getParent());

        $this->assertEquals('Then', $steps[1]->getType());
        $this->assertEquals('Do', $steps[1]->getText());
        $this->assertSame($background, $steps[1]->getParent());
    }

    public function testFeature()
    {
        $background = new BackgroundNode();
        $this->assertNull($background->getFeature());

        $background->setFeature($feature = new FeatureNode());
        $this->assertSame($feature, $background->getFeature());
    }
}
