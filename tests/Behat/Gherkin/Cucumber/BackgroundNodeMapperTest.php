<?php

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Cucumber\BackgroundNodeMapper;
use Behat\Gherkin\Cucumber\KeywordTypeMapper;
use Behat\Gherkin\Cucumber\PyStringNodeMapper;
use Behat\Gherkin\Cucumber\StepNodeMapper;
use Behat\Gherkin\Cucumber\TableNodeMapper;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\StepNode;
use Cucumber\Messages\Background;
use Cucumber\Messages\FeatureChild;
use Cucumber\Messages\Location;
use Cucumber\Messages\Step;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class BackgroundNodeMapperTest extends TestCase
{
    /**
     * @var BackgroundNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new BackgroundNodeMapper(
            new StepNodeMapper(
                new KeywordTypeMapper(),
                new PyStringNodeMapper(),
                new TableNodeMapper()
            )
        );
    }

    public function testItReturnsNullIfNoChildrenAreBackgrounds()
    {
        $result = $this->mapper->map([]);

        self::assertNull($result);
    }

    public function testItReturnsABackgroundNodeIfOneIsPresent()
    {
        $result = $this->mapper->map([
            new FeatureChild(null, new Background())
        ]);

        self::assertInstanceOf(BackgroundNode::class, $result);
    }

    public function testItPopulatesTitle()
    {
        $result = $this->mapper->map([new FeatureChild(null,
            new Background(new Location(),'','Background title','')
        )]);

        self::assertSame('Background title', $result->getTitle());
    }

    public function testItPopulatesTitleFromDescriptionWhenMultiline()
    {
        $result = $this->mapper->map([new FeatureChild(null,
            new Background(new Location(), '', 'title', "across\nmany\nlines")
        )]);

        self::assertSame("title\nacross\nmany\nlines", $result->getTitle());
    }

    public function testItPopulatesKeyword()
    {
        $result = $this->mapper->map([new FeatureChild(null,
            new Background(new Location(),'Background','','')
        )]);

        self::assertSame('Background', $result->getKeyword());
    }

    public function testItPopulatesLine()
    {
        $result = $this->mapper->map([new FeatureChild(null,
            new Background(new Location(100, 0))
        )]);

        self::assertSame(100, $result->getLine());
    }

    public function testItPopulatesSteps()
    {
        $result = $this->mapper->map([new FeatureChild(null,
            new Background(new Location(), '', '', '', [
                new Step()
            ])
        )]);

        self::assertCount(1, $result->getSteps());
        self::assertInstanceOf(StepNode::class, $result->getSteps()[0]);
    }
}
