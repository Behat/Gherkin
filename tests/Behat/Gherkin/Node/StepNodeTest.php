<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\FeatureNode;

class StepNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testType()
    {
        $step = new StepNode('When');
        $this->assertEquals('When', $step->getType());

        $step->setType('Given');
        $this->assertEquals('Given', $step->getType());
    }

    public function testText()
    {
        $step = new StepNode('When');
        $this->assertNull($step->getText());

        $step->setText('Some definition');
        $this->assertEquals('Some definition', $step->getText());

        $step = new StepNode('When', 'Some action');
        $this->assertEquals('Some action', $step->getText());

        $step->setText('Some "<text>" in <string>');
        $this->assertEquals('Some "<text>" in <string>', $step->getText());
    }

    public function testTokens()
    {
        $step = new StepNode('When', 'Some "<text>" in <string>');

        $scenario = new ScenarioNode();
        $scenario->addStep($step);

        $feature = new FeatureNode();
        $feature->addScenario($scenario);
        $feature->freeze();

        $step1 = $step->createExampleRowStep(array('text' => 'change'));
        $this->assertNotSame($step, $step1);
        $this->assertEquals('Some "change" in <string>', $step1->getText());
        $this->assertEquals('Some "<text>" in <string>', $step1->getCleanText());

        $step2 = $step->createExampleRowStep(array('text' => 'change', 'string' => 'browser'));
        $this->assertNotSame($step, $step2);
        $this->assertEquals('Some "change" in browser', $step2->getText());
        $this->assertEquals('Some "<text>" in <string>', $step2->getCleanText());
    }

    public function testArguments()
    {
        $step = new StepNode('Given', null);
        $this->assertEquals(0, count($step->getArguments()));
        $this->assertFalse($step->hasArguments());

        $step->addArgument(new PyStringNode());
        $this->assertEquals(1, count($step->getArguments()));
        $this->assertTrue($step->hasArguments());

        $step->addArgument(new TableNode());
        $this->assertEquals(2, count($step->getArguments()));
        $this->assertTrue($step->hasArguments());

        $arguments = $step->getArguments();
        $this->assertInstanceOf('Behat\Gherkin\Node\PyStringNode', $arguments[0]);
        $this->assertInstanceOf('Behat\Gherkin\Node\TableNode', $arguments[1]);
    }

    public function testParent()
    {
        $step = new StepNode('Given');
        $this->assertNull($step->getParent());

        $step->setParent($scenario = new ScenarioNode());
        $this->assertSame($scenario, $step->getParent());
    }

    public function testLine()
    {
        $step = new StepNode('Given');
        $this->assertEquals(0, $step->getLine());

        $step = new StepNode('Given', null, 23);
        $this->assertEquals(23, $step->getLine());
    }
}
