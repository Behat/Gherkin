<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\StepNode;
use PHPUnit\Framework\TestCase;

class OutlineNodeTest extends TestCase
{
    public function testCreatesExamplesForExampleTable()
    {
        $steps = array(
            new StepNode('Gangway!', 'I am <name>', array(), null, 'Given'),
            new StepNode('Aye!', 'my email is <email>', array(), null, 'And'),
            new StepNode('Blimey!', 'I open homepage', array(), null, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', array(), null, 'Then'),
        );

        $table = new ExampleTableNode(array(
            2 => array('name', 'email'),
            22 => array('everzet', 'ever.zet@gmail.com'),
            23 => array('example', 'example@example.com')
        ), 'Examples');

        $outline = new OutlineNode(null, array(), $steps, $table, null, null);

        $this->assertCount(2, $examples = $outline->getExamples());
        $this->assertEquals(22, $examples[0]->getLine());
        $this->assertEquals(23, $examples[1]->getLine());
        $this->assertEquals(array('name' => 'everzet', 'email' => 'ever.zet@gmail.com'), $examples[0]->getTokens());
        $this->assertEquals(array('name' => 'example', 'email' => 'example@example.com'), $examples[1]->getTokens());
    }

    public function testCreatesExamplesForExampleTableWithSeveralExamplesAndTags()
    {
        $steps = array(
            new StepNode('Gangway!', 'I am <name>', array(), null, 'Given'),
            new StepNode('Aye!', 'my email is <email>', array(), null, 'And'),
            new StepNode('Blimey!', 'I open homepage', array(), null, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', array(), null, 'Then'),
        );

        $table = new ExampleTableNode(array(
            2 => array('name', 'email'),
            22 => array('everzet', 'ever.zet@gmail.com'),
            23 => array('example', 'example@example.com')
        ), 'Examples', array());

        $table2 = new ExampleTableNode(array(
            3 => array('name', 'email'),
            32 => array('everzet2', 'ever.zet2@gmail.com'),
            33 => array('example2', 'example2@example.com')
        ), 'Examples', array('etag1', 'etag2'));

        $outline = new OutlineNode(null, array('otag1', 'otag2'), $steps, array($table, $table2), null, null);

        $this->assertCount(4, $examples = $outline->getExamples());
        $this->assertEquals(22, $examples[0]->getLine());
        $this->assertEquals(23, $examples[1]->getLine());
        $this->assertEquals(32, $examples[2]->getLine());
        $this->assertEquals(33, $examples[3]->getLine());
        $this->assertEquals(array('name' => 'everzet', 'email' => 'ever.zet@gmail.com'), $examples[0]->getTokens());
        $this->assertEquals(array('name' => 'example', 'email' => 'example@example.com'), $examples[1]->getTokens());
        $this->assertEquals(array('name' => 'everzet2', 'email' => 'ever.zet2@gmail.com'), $examples[2]->getTokens());
        $this->assertEquals(array('name' => 'example2', 'email' => 'example2@example.com'), $examples[3]->getTokens());

        for ($i = 0; $i < 2; $i++) {
            foreach (array('otag1', 'otag2') as $tag) {
                $this->assertTrue($examples[$i]->hasTag($tag), "there is no tag " . $tag . " in example #" . $i);
            }
        }

        for ($i = 2; $i < 4; $i++) {
            foreach (array('otag1', 'otag2', 'etag1', 'etag2') as $tag) {
                $this->assertTrue($examples[$i]->hasTag($tag), "there is no tag " . $tag . " in example #" . $i);
            }
        }
    }

    public function testCreatesEmptyExamplesForEmptyExampleTable()
    {
        $steps = array(
            new StepNode('Gangway!', 'I am <name>', array(), null, 'Given'),
            new StepNode('Aye!', 'my email is <email>', array(), null, 'And'),
            new StepNode('Blimey!', 'I open homepage', array(), null, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', array(), null, 'Then'),
        );

        $table = new ExampleTableNode(array(
            array('name', 'email')
        ), 'Examples');

        $outline = new OutlineNode(null, array(), $steps, $table, null, null);

        $this->assertCount(0, $examples = $outline->getExamples());
    }

    public function testCreatesEmptyExamplesForNoExampleTable()
    {
        $steps = array(
            new StepNode('Gangway!', 'I am <name>', array(), null, 'Given'),
            new StepNode('Aye!', 'my email is <email>', array(), null, 'And'),
            new StepNode('Blimey!', 'I open homepage', array(), null, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', array(), null, 'Then'),
        );

        $table = new ExampleTableNode(array(), 'Examples');

        $outline = new OutlineNode(null, array(), $steps, array($table), null, null);

        $this->assertCount(0, $examples = $outline->getExamples());
    }

    public function testPopulatesExampleWithOutlineTitle()
    {
        $steps = array(
            new StepNode('', 'I am <name>', array(), null, 'Given'),
        );

        $table = new ExampleTableNode(array(
            10 => array('name', 'email'),
            11 => array('Ciaran', 'ciaran@example.com'),
        ), 'Examples');

        $outline = new OutlineNode('An outline title', array(), $steps, $table, null, null);

        $this->assertCount(1, $examples = $outline->getExamples());
        $this->assertEquals('An outline title', current($examples)->getOutlineTitle());
    }
}
