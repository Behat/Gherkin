<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Cucumber\Messages\DataTable;
use Cucumber\Messages\DocString;
use Cucumber\Messages\Location;
use Cucumber\Messages\Step;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class StepNodeMapperTest extends TestCase
{
    /**
     * @var StepNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new StepNodeMapper(
            new KeywordTypeMapper(),
            new PyStringNodeMapper(),
            new TableNodeMapper()
        );
    }

    public function testItMapsEmptyArray()
    {
        $steps = $this->mapper->map([]);

        self::assertSame([], $steps);
    }

    public function testItMapsSteps()
    {
        $steps = $this->mapper->map([new Step()]);

        self::assertCount(1, $steps);
        self::assertInstanceOf(StepNode::class, $steps[0]);
    }

    public function testItMapsKeyword()
    {
        $steps = $this->mapper->map([new Step(
            new Location(), 'Given '
        )]);
        $step = $steps[0];

        self::assertSame('Given', $step->getKeyword());
    }

    public function testItMapsText()
    {
        $steps = $this->mapper->map([new Step(
            new Location(), '', null, 'I have five carrots'
        )]);
        $step = $steps[0];

        self::assertSame('I have five carrots', $step->getText());
    }

    public function testItMapsLine()
    {
        $steps = $this->mapper->map([new Step(
            new Location(100, 0)
        )]);
        $step = $steps[0];

        self::assertSame(100, $step->getLine());
    }

    public function testItMapsKeywordType()
    {
        $steps = $this->mapper->map([new Step(
            new Location(), '', Step\KeywordType::CONTEXT
        )]);
        $step = $steps[0];

        self::assertSame('Given', $step->getKeywordType());
    }

    public function testItMapsDocstringsToArguments()
    {
        $steps = $this->mapper->map([new Step(
            new Location(), '', null,'', new DocString()
        )]);
        $step = $steps[0];

        self::assertCount(1, $step->getArguments());
        self::assertInstanceOf(PyStringNode::class, $step->getArguments()[0]);
    }

    public function testItMapsTablesToArguments()
    {
        $steps = $this->mapper->map([new Step(
            new Location(), '', null,'', null, new DataTable()
        )]);
        $step = $steps[0];

        self::assertCount(1, $step->getArguments());
        self::assertInstanceOf(TableNode::class, $step->getArguments()[0]);
    }
}
