<?php

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Cucumber\BackgroundNodeMapper;
use Behat\Gherkin\Cucumber\ExampleTableNodeMapper;
use Behat\Gherkin\Cucumber\FeatureNodeMapper;
use Behat\Gherkin\Cucumber\KeywordTypeMapper;
use Behat\Gherkin\Cucumber\PyStringNodeMapper;
use Behat\Gherkin\Cucumber\ScenarioNodeMapper;
use Behat\Gherkin\Cucumber\StepNodeMapper;
use Behat\Gherkin\Cucumber\TableNodeMapper;
use Behat\Gherkin\Cucumber\TagMapper;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Cucumber\Messages\Background;
use Cucumber\Messages\Feature;
use Cucumber\Messages\FeatureChild;
use Cucumber\Messages\GherkinDocument;
use Cucumber\Messages\Location;
use Cucumber\Messages\Scenario;
use Cucumber\Messages\Tag;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class FeatureNodeMapperTest extends TestCase
{
    public function setUp() : void
    {
        $tagMapper = new TagMapper();
        $stepNodeMapper = new StepNodeMapper(
            new KeywordTypeMapper(),
            new PyStringNodeMapper(),
            new TableNodeMapper()
        );
        $this->mapper = new FeatureNodeMapper(
            $tagMapper,
            new BackgroundNodeMapper(
                $stepNodeMapper
            ),
            new ScenarioNodeMapper(
                $tagMapper,
                $stepNodeMapper,
                new ExampleTableNodeMapper(
                    $tagMapper
                )
            )
        );
    }

    public function testItReturnsNullIfThereIsNoFeature()
    {
        $result = $this->mapper->map(new GherkinDocument());

        self::assertSame(null, $result);
    }

    public function testItReturnsAFeatureIfThereIsOne()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature()
        ));

        self::assertInstanceOf(FeatureNode::class, $feature);
    }

    public function testItMapsTheTitle()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', 'This is the feature title')
        ));

        self::assertSame('This is the feature title', $feature->getTitle());
    }

    public function testItMapsTheDescription()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', 'This is the feature description')
        ));

        self::assertSame('This is the feature description', $feature->getDescription());
    }

    public function testItTrimsTheDescription()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(0,1), [], '', '', '', '  This is the feature description')
        ));

        self::assertSame('This is the feature description', $feature->getDescription());
    }

    public function testItMapsTheKeyword()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', 'Given')
        ));

        self::assertSame('Given', $feature->getKeyword());
    }

    public function testItMapsTheLanguage()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], 'zh')
        ));

        self::assertSame('zh', $feature->getLanguage());
    }

    public function testItMapsTheFile()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '/foo/bar.feature', new Feature()
        ));

        self::assertSame('/foo/bar.feature', $feature->getFile());
    }

    public function testItMapsTheLine()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(100,0))
        ));

        self::assertSame(100, $feature->getLine());
    }

    public function testItMapsTheTags()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(),[
                new Tag(new Location(), '@foo'),
                new Tag(new Location(), '@bar')
            ])
        ));

        self::assertSame(['foo', 'bar'], $feature->getTags());
    }

    public function testItMapsTheBackground()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', '',
            [new FeatureChild(null, new Background())]
            )
        ));

        self::assertInstanceOf(BackgroundNode::class, $feature->getBackground());
    }

    public function testItMapsScenarios()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', '',
                [new FeatureChild(null, null, new Scenario())]
            )
        ));

        self::assertCount(1, $feature->getScenarios());
    }
}
