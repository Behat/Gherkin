<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use Cucumber\Messages\Examples;
use Cucumber\Messages\FeatureChild;
use Cucumber\Messages\Location;
use Cucumber\Messages\Scenario;
use Cucumber\Messages\Step;
use Cucumber\Messages\Tag;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class ScenarioNodeMapperTest extends TestCase
{
    /**
     * @var ScenarioNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new ScenarioNodeMapper(
            new TagMapper(),
            new StepNodeMapper(
                new KeywordTypeMapper(),
                new PyStringNodeMapper(),
                new TableNodeMapper()
            ),
            new ExampleTableNodeMapper()
        );
    }

    public function testItMapsEmptyArrayToEmpty()
    {
        $result = $this->mapper->map([]);

        self::assertSame([], $result);
    }

    public function testItMapsAScenario()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location())
        )]);

        self::assertCount(1, $scenarios);
        self::assertInstanceOf(ScenarioNode::class, $scenarios[0]);
    }

    public function testItMapsScenarioTitle()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], '', 'Scenario title')
        )]);

        self::assertCount(1, $scenarios);
        self::assertSame('Scenario title', $scenarios[0]->getTitle());
    }

    public function testItMapsDescriptionAsMultiLineScenarioTitle()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], '', 'title', "across\nmany\nlines")
        )]);

        self::assertCount(1, $scenarios);
        self::assertSame("title\nacross\nmany\nlines", $scenarios[0]->getTitle());
    }

    public function testItMapsScenarioKeyword()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], 'Scenario', '')
        )]);

        self::assertCount(1, $scenarios);
        self::assertSame('Scenario', $scenarios[0]->getKeyword());
    }

    public function testItMapsScenarioLine()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(100, 0))
        )]);

        self::assertCount(1, $scenarios);
        self::assertSame(100, $scenarios[0]->getLine());
    }

    public function testItMapsScenarioTags()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [new Tag(new Location(), 'foo')])
        )]);

        self::assertCount(1, $scenarios);
        self::assertSame(['foo'], $scenarios[0]->getTags());
    }

    public function testItMapsScenarioSteps()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], '', '', '',
                [new Step() ]
            )
        )]);

        self::assertCount(1, $scenarios);
        self::assertCount(1, $scenarios[0]->getSteps());
    }

    public function testItMapsScenarioWithExamplesAsScenarioOutline()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], '', '', '', [],
                [new Examples()]
            )
        )]);

        self::assertCount(1, $scenarios);
        self::assertInstanceOf(OutlineNode::class, $scenarios[0]);
    }

    public function testItMapsExamples()
    {
        $scenarios = $this->mapper->map([new FeatureChild(null, null,
            new Scenario(new Location(), [], '', '', '', [],
                [new Examples()]
            )
        )]);

        self::assertCount(1, $scenarios);
        self::assertCount(1, $scenarios[0]->getExampleTables());
    }
}
