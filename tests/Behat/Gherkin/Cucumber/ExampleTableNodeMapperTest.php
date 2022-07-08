<?php

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\DataTable;
use Cucumber\Messages\Examples;
use Cucumber\Messages\Location;
use Cucumber\Messages\TableCell;
use Cucumber\Messages\TableRow;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber
 */
final class ExampleTableNodeMapperTest extends TestCase
{
    /**
     * @var ExampleTableNodeMapper
     */
    private $mapper;

    public function setUp() : void
    {
        $this->mapper = new ExampleTableNodeMapper();
    }

    public function testItMapsEmptyArrayToEmpty()
    {
        $result = $this->mapper->map([]);

        self::assertSame([], $result);
    }

    public function testItMapsKeyword()
    {
        $tables = $this->mapper->map([
            new Examples(new Location(), [], 'Examples')
        ]);

        self::assertCount(1, $tables);
        self::assertSame('Examples', $tables[0]->getKeyword());
    }

    public function testItMapsHeaderAndRowsIntoOneTable()
    {
        {
            $tables = $this->mapper->map([
                new Examples(new Location(), [], '','','',
                    new TableRow(new Location(100, 0), [
                        new TableCell(new Location(), 'header-1'),
                        new TableCell(new Location(), 'header-2'),
                    ]),
                    [
                        new TableRow(new Location(101, 0), [
                            new TableCell(new Location(), 'value-3'),
                            new TableCell(new Location(), 'value-4'),
                        ]),
                        new TableRow(new Location(102, 0), [
                            new TableCell(new Location(), 'value-5'),
                            new TableCell(new Location(), 'value-6'),
                        ])
                    ]
                )
            ]);

            $expectedHash = [
                [
                    'header-1'=>'value-3',
                    'header-2'=>'value-4'
                ],
                [
                    'header-1'=>'value-5',
                    'header-2'=>'value-6'
                ]
            ];

            self::assertCount(1, $tables);
            self::assertSame($expectedHash, $tables[0]->getHash());
        }
    }

    public function testItMapsLineFromHeaderRow()
    {
        $tables = $this->mapper->map([
            new Examples(new Location(), [], '','','',
                new TableRow(new Location(100, 0), [])
            )
        ]);

        self::assertCount(1, $tables);
        self::assertSame(100, $tables[0]->getLine());
    }

}
