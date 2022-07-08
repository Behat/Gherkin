<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
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
                new ExampleTableNodeMapper()
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

    public function testItPopulatesTheTitle()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', 'This is the feature title')
        ));

        self::assertSame('This is the feature title', $feature->getTitle());
    }

    public function testItPopulatesTheDescription()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', 'This is the feature description')
        ));

        self::assertSame('This is the feature description', $feature->getDescription());
    }

    public function testItPopulatesTheKeyword()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', 'Given')
        ));

        self::assertSame('Given', $feature->getKeyword());
    }

    public function testItPopulatesTheLanguage()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], 'zh')
        ));

        self::assertSame('zh', $feature->getLanguage());
    }

    public function testItPopulatesTheFile()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '/foo/bar.feature', new Feature()
        ));

        self::assertSame('/foo/bar.feature', $feature->getFile());
    }

    public function testItPopulatesTheLine()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(100,0))
        ));

        self::assertSame(100, $feature->getLine());
    }

    public function testItPopulatesTheTags()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(),[
                new Tag(new Location(), '@foo'),
                new Tag(new Location(), '@bar')
            ])
        ));

        self::assertSame(['foo', 'bar'], $feature->getTags());
    }

    public function testItPopulatesTheBackground()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', '',
            [new FeatureChild(null, new Background())]
            )
        ));

        self::assertInstanceOf(BackgroundNode::class, $feature->getBackground());
    }

    public function testItPopulatesScenarios()
    {
        $feature = $this->mapper->map(new GherkinDocument(
            '', new Feature(new Location(), [], '', '', '', '',
                [new FeatureChild(null, null, new Scenario())]
            )
        ));

        self::assertCount(1, $feature->getScenarios());
    }
}
