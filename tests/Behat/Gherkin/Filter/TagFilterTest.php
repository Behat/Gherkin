<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;

class TagFilterTest extends TestCase
{
    public function testFilterFeature()
    {
        $feature = new FeatureNode(null, null, array('wip'), null, array(), null, null, null, 1);
        $filter = new TagFilter('@wip');
        $this->assertEquals($feature, $filter->filterFeature($feature));

        $scenarios = array(
            new ScenarioNode(null, array(), array(), null, 2),
            $matchedScenario = new ScenarioNode(null, array('wip'), array(), null, 4)
        );
        $feature = new FeatureNode(null, null, array(), null, $scenarios, null, null, null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame(array($matchedScenario), $filteredFeature->getScenarios());

        $filter = new TagFilter('~@wip');
        $scenarios = array(
            $matchedScenario = new ScenarioNode(null, array(), array(), null, 2),
            new ScenarioNode(null, array('wip'), array(), null, 4)
        );
        $feature = new FeatureNode(null, null, array(), null, $scenarios, null, null, null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame(array($matchedScenario), $filteredFeature->getScenarios());
    }

    public function testIsFeatureMatchFilter()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, null, 1);

        $filter = new TagFilter('@wip');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('wip'), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('~@done');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('wip', 'done'), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('tag1', 'tag2', 'tag3'), null, array(), null, null, null, 1);
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array(
            'tag1',
            'tag2',
            'tag3',
            'tag5'
        ), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip&&@vip');
        $feature = new FeatureNode(null, null, array('wip', 'done'), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('wip', 'done', 'vip'), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip,@vip&&@user');
        $feature = new FeatureNode(null, null, array('wip'), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('vip'), null, array(), null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('wip', 'user'), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, array('vip', 'user'), null, array(), null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $feature = new FeatureNode(null, null, array('feature-tag'), null, array(), null, null, null, 1);
        $scenario = new ScenarioNode(null, array(), array(), null, 2);

        $filter = new TagFilter('@wip');
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('~@done');
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, array(
            'tag1',
            'tag2',
            'tag3'
        ), array(), null, 2);
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, array(
            'tag1',
            'tag2',
            'tag3',
            'tag5'
        ), array(), null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@wip&&@vip');
        $scenario = new ScenarioNode(null, array('wip', 'not-done'), array(), null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, array(
            'wip',
            'not-done',
            'vip'
        ), array(), null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@wip,@vip&&@user');
        $scenario = new ScenarioNode(null, array(
            'wip'
        ), array(), null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, array('vip'), array(), null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, array('wip', 'user'), array(), null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@feature-tag&&@user');
        $scenario = new ScenarioNode(null, array('wip', 'user'), array(), null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@feature-tag&&@user');
        $scenario = new ScenarioNode(null, array('wip'), array(), null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new OutlineNode(null, array('wip'), array(), array(
            new ExampleTableNode(array(), null, array('etag1', 'etag2')),
            new ExampleTableNode(array(), null, array('etag2', 'etag3')),
        ), null, 2);

        $tagFilter = new TagFilter('@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('~@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@wip&&@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&@etag1&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&~@etag11111&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&~@etag1&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&@etag2');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('~@etag1&&~@etag3');
        $this->assertFalse($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@etag1&&@etag3');
        $this->assertFalse($tagFilter->isScenarioMatch($feature, $scenario), "Tags from different examples tables");
    }

    public function testFilterFeatureWithTaggedExamples()
    {
        $exampleTableNode1 = new ExampleTableNode(array(), null, array('etag1', 'etag2'));
        $exampleTableNode2 = new ExampleTableNode(array(), null, array('etag2', 'etag3'));
        $scenario = new OutlineNode(null, array('wip'), array(), array(
            $exampleTableNode1,
            $exampleTableNode2,
        ), null, 2);
        $feature = new FeatureNode(null, null, array('feature-tag'), null, array($scenario), null, null, null, 1);

        $tagFilter = new TagFilter('@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@etag1');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode1), $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('~@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode1), $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@wip&&@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode2), $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode1), $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&~@etag11111&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@feature-tag&&~@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode2), $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $exampleTableNode1 = new ExampleTableNode(array(), null, array('etag1', 'etag'));
        $exampleTableNode2 = new ExampleTableNode(array(), null, array('etag2', 'etag22', 'etag'));
        $exampleTableNode3 = new ExampleTableNode(array(), null, array('etag3', 'etag22', 'etag'));
        $exampleTableNode4 = new ExampleTableNode(array(), null, array('etag4', 'etag'));
        $scenario1 = new OutlineNode(null, array('wip'), array(), array(
            $exampleTableNode1,
            $exampleTableNode2,
        ), null, 2);
        $scenario2 = new OutlineNode(null, array('wip'), array(), array(
            $exampleTableNode3,
            $exampleTableNode4,
        ), null, 2);
        $feature = new FeatureNode(null, null, array('feature-tag'), null, array($scenario1, $scenario2), null, null, null, 1);

        $tagFilter = new TagFilter('@etag');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals(array($scenario1, $scenario2), $scenarioInterfaces);

        $tagFilter = new TagFilter('@etag22');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals(2, count($scenarioInterfaces));
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode2), $scenarioInterfaces[0]->getExampleTables());
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals(array($exampleTableNode3), $scenarioInterfaces[1]->getExampleTables());
    }

    public function testFilterWithWhitespaceIsDeprecated()
    {
        $this->expectDeprecation();
        $tagFilter = new TagFilter('@tag with space');
        $scenario = new ScenarioNode(null, ['tag with space'], array(), null, 2);
        $feature = new FeatureNode(null, null, [], null, [$scenario], null, null, null, 1);

        $scenarios = $tagFilter->filterFeature($feature)->getScenarios();

        $this->assertEquals([$scenario], $scenarios);
    }
}
