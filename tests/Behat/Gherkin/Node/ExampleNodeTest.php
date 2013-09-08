<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

class ExampleNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateExampleSteps()
    {
        $steps = array(
            $step1 = new StepNode('Given', 'I am <name>', array(), null),
            $step2 = new StepNode('And', 'my email is <email>', array(), null),
            $step3 = new StepNode('When', 'I open homepage', array(), null),
            $step4 = new StepNode('Then', 'website should recognise me', array(), null),
        );

        $table = new ExampleTableNode(array(
            array('name', 'email'),
            array('everzet', 'ever.zet@gmail.com'),
            array('example', 'example@example.com')
        ), 'Examples');

        $outline = new OutlineNode(null, array(), $steps, $table, null, null);
        $examples = $outline->getExamples();

        $this->assertCount(4, $steps = $examples[0]->getSteps());

        $this->assertEquals('Given', $steps[0]->getType());
        $this->assertEquals('I am everzet', $steps[0]->getText());
        $this->assertEquals('And', $steps[1]->getType());
        $this->assertEquals('my email is ever.zet@gmail.com', $steps[1]->getText());
        $this->assertEquals('When', $steps[2]->getType());
        $this->assertEquals('I open homepage', $steps[2]->getText());

        $this->assertCount(4, $steps = $examples[1]->getSteps());

        $this->assertEquals('Given', $steps[0]->getType());
        $this->assertEquals('I am example', $steps[0]->getText());
        $this->assertEquals('And', $steps[1]->getType());
        $this->assertEquals('my email is example@example.com', $steps[1]->getText());
        $this->assertEquals('When', $steps[2]->getType());
        $this->assertEquals('I open homepage', $steps[2]->getText());
    }

    public function testCreateExampleStepsWithArguments()
    {
        $steps = array(
            $step1 = new StepNode('Given', 'I am <name>', array(), null),
            $step2 = new StepNode('And', 'my email is <email>', array(), null),
            $step3 = new StepNode('When', 'I open:', array(
                new PyStringNode(array('page: <url>'), null)
            ), null),
            $step4 = new StepNode('Then', 'website should recognise me', array(
                new TableNode(array(array('page', '<url>')))
            ), null),
        );

        $table = new ExampleTableNode(array(
            array('name', 'email', 'url'),
            array('everzet', 'ever.zet@gmail.com', 'homepage'),
            array('example', 'example@example.com', 'other page')
        ), 'Examples');

        $outline = new OutlineNode(null, array(), $steps, $table, null, null);
        $examples = $outline->getExamples();

        $steps = $examples[0]->getSteps();

        $args = $steps[2]->getArguments();
        $this->assertEquals('page: homepage', $args[0]->getRaw());

        $args = $steps[3]->getArguments();
        $this->assertEquals('| page | homepage |', $args[0]->getTableAsString());
    }
}
