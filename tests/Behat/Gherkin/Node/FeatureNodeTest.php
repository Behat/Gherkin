<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\OutlineNode;

class FeatureNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testTitle()
    {
        $feature = new FeatureNode();
        $this->assertNull($feature->getTitle());

        $feature->setTitle('test title 1');
        $this->assertEquals('test title 1', $feature->getTitle());

        $feature = new FeatureNode('test title 2');
        $this->assertEquals('test title 2', $feature->getTitle());
    }

    public function testDescription()
    {
        $feature = new FeatureNode();
        $this->assertNull($feature->getDescription());

        $feature->setDescription('test description 1');
        $this->assertEquals('test description 1', $feature->getDescription());

        $feature = new FeatureNode(null, 'test description 2');
        $this->assertEquals('test description 2', $feature->getDescription());
    }

    public function testLanguage()
    {
        $feature = new FeatureNode();
        $this->assertEquals('en', $feature->getLanguage());

        $feature->setLanguage('ru');
        $this->assertEquals('ru', $feature->getLanguage());
    }

    public function testBackground()
    {
        $feature = new FeatureNode();
        $this->assertNull($feature->getBackground());
        $this->assertFalse($feature->hasBackground());

        $feature->setBackground($background = new BackgroundNode());
        $this->assertSame($feature, $feature->getBackground()->getFeature());
        $this->assertSame($background, $feature->getBackground());
        $this->assertTrue($feature->hasBackground());
    }

    public function testFile()
    {
        $feature = new FeatureNode();
        $this->assertNull($feature->getFile());

        $feature = new FeatureNode(null, null, 'path/to/file_2');
        $this->assertEquals('path/to/file_2', $feature->getFile());
    }

    public function testLine()
    {
        $feature = new FeatureNode();
        $this->assertEquals(0, $feature->getLine());

        $feature = new FeatureNode(null, null, null, 23);
        $this->assertEquals(23, $feature->getLine());
    }

    public function testScenarios()
    {
        $feature = new FeatureNode();
        $this->assertEquals(0, count($feature->getScenarios()));
        $this->assertFalse($feature->hasScenarios());

        $feature->addScenario(new ScenarioNode());
        $this->assertEquals(1, count($feature->getScenarios()));
        $this->assertTrue($feature->hasScenarios());

        $feature->addScenario(new OutlineNode());
        $this->assertEquals(2, count($feature->getScenarios()));
        $this->assertTrue($feature->hasScenarios());

        $scenarios = $feature->getScenarios();
        $this->assertInstanceOf('Behat\Gherkin\Node\ScenarioNode', $scenarios[0]);
        $this->assertSame($feature, $scenarios[0]->getFeature());
        $this->assertInstanceOf('Behat\Gherkin\Node\OutlineNode', $scenarios[1]);
        $this->assertSame($feature, $scenarios[1]->getFeature());
    }

    public function testTags()
    {
        $feature = new FeatureNode();
        $this->assertFalse($feature->hasTags());
        $this->assertInternalType('array', $feature->getTags());
        $this->assertEquals(0, count($feature->getTags()));

        $feature->setTags($tags = array('tag1', 'tag2'));
        $this->assertEquals($tags, $feature->getTags());

        $feature->addTag('tag3');
        $this->assertEquals(array('tag1', 'tag2', 'tag3'), $feature->getTags());

        $this->assertFalse($feature->hasTag('tag4'));
        $this->assertTrue($feature->hasTag('tag2'));
        $this->assertTrue($feature->hasTag('tag3'));
    }
}
