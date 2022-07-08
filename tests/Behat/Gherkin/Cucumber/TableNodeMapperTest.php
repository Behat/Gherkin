<?php

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\DataTable;
use Cucumber\Messages\Location;
use Cucumber\Messages\TableCell;
use Cucumber\Messages\TableRow;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class TableNodeMapperTest extends TestCase
{
    /**
     * @var TableNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new TableNodeMapper();
    }

    public function testItMapsNullToEmptyArray()
    {
        $result = $this->mapper->map(null);

        self::assertSame([], $result);
    }

    public function testItMapsCells()
    {
        $tables = $this->mapper->map(
            new DataTable(new Location(), [
                new TableRow(new Location(), [
                    new TableCell(new Location(100, 10), 'foo'),
                    new TableCell(new Location(100, 20), 'bar')
                ]),
                new TableRow(new Location(101, 0), [
                    new TableCell(new Location(101, 10), 'baz'),
                    new TableCell(new Location(101, 20), 'boz')
                ])
            ])
        );

        self::assertCount(1, $tables);
        self::assertSame(['foo', 'bar'], $tables[0]->getRow(0));
        self::assertSame(['baz', 'boz'], $tables[0]->getRow(1));
    }

    public function testItMapsLineFromFirstTableRow()
    {
        $tables = $this->mapper->map(
            new DataTable(new Location(), [
                new TableRow(new Location(100, 10), [
                    new TableCell(new Location(), 'foo')
                ])
            ])
        );

        self::assertCount(1, $tables);
        self::assertSame(100, $tables[0]->getLine());
    }
}
