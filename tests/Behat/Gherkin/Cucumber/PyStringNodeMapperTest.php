<?php

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Cucumber\PyStringNodeMapper;
use Cucumber\Messages\DocString;
use Cucumber\Messages\Location;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class PyStringNodeMapperTest extends TestCase
{
    /**
     * @var PyStringNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new PyStringNodeMapper();
    }

    public function testItMapsNullToEmpty()
    {
        $result = $this->mapper->map(null);

        self::assertSame([], $result);
    }

    public function testItMapsLine()
    {
        $stringNodes = $this->mapper->map(new DocString(new Location(100, 0)));

        self::assertCount(1, $stringNodes);
        self::assertSame(100, $stringNodes[0]->getLine());
    }

    public function testItMapsStringSplitByNewline()
    {
        $stringNodes = $this->mapper->map(new DocString(new Location(), '', "foo\nbar\r\nbaz"));

        self::assertCount(1, $stringNodes);
        self::assertSame(['foo','bar','baz'], $stringNodes[0]->getStrings());
    }
}
