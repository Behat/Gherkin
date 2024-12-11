<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase;

class TableNodeTest extends TestCase
{
    public function testConstructorExpectsSameNumberOfColumnsInEachRow()
    {
        $this->expectException(NodeException::class);
        new TableNode([
            ['username', 'password'],
            ['everzet'],
            ['antono', 'pa$sword'],
        ]);
    }

    public function testConstructorExpectsTwoDimensionalArray()
    {
        $this->expectException(NodeException::class);
        $this->expectExceptionMessage("Table row '0' is expected to be array, got string");
        new TableNode([
            'everzet', 'antono',
        ]);
    }

    public function testConstructorExpectsScalarCellValue()
    {
        $this->expectException(NodeException::class);
        $this->expectExceptionMessage("Table cell at row '0', col '0' is expected to be scalar, got array");
        new TableNode([
            [['everzet', 'antono']],
        ]);
    }

    public function testConstructorExpectsEqualRowLengths()
    {
        $this->expectException(NodeException::class);
        $this->expectExceptionMessage("Table row '1' is expected to have 2 columns, got 1");
        new TableNode([
            ['everzet', 'antono'],
            ['everzet'],
        ]);
    }

    public function testHashTable()
    {
        $table = new TableNode([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(
            [
                ['username' => 'everzet', 'password' => 'qwerty'], ['username' => 'antono', 'password' => 'pa$sword'],
            ],
            $table->getHash()
        );

        $table = new TableNode([
            ['username', 'password'],
            ['', 'qwerty'],
            ['antono', ''],
            ['', ''],
        ]);

        $this->assertEquals(
            [
                ['username' => '', 'password' => 'qwerty'],
                ['username' => 'antono', 'password' => ''],
                ['username' => '', 'password' => ''],
            ],
            $table->getHash()
        );
    }

    public function testIterator()
    {
        $table = new TableNode([
            ['username', 'password'],
            ['', 'qwerty'],
            ['antono', ''],
            ['', ''],
        ]);

        $this->assertEquals(
            [
                ['username' => '', 'password' => 'qwerty'],
                ['username' => 'antono', 'password' => ''],
                ['username' => '', 'password' => ''],
            ],
            iterator_to_array($table)
        );
    }

    public function testRowsHashTable()
    {
        $table = new TableNode([
            ['username', 'everzet'],
            ['password', 'qwerty'],
            ['uid', '35'],
        ]);

        $this->assertEquals(
            ['username' => 'everzet', 'password' => 'qwerty', 'uid' => '35'],
            $table->getRowsHash()
        );
    }

    public function testLongRowsHashTable()
    {
        $table = new TableNode([
            ['username', 'everzet', 'marcello'],
            ['password', 'qwerty', '12345'],
            ['uid', '35', '22'],
        ]);

        $this->assertEquals([
            'username' => ['everzet', 'marcello'],
            'password' => ['qwerty', '12345'],
            'uid' => ['35', '22'],
        ], $table->getRowsHash());
    }

    public function testGetRows()
    {
        $table = new TableNode([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ]);

        $this->assertEquals([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ], $table->getRows());
    }

    public function testGetLines()
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals([5, 10, 13], $table->getLines());
    }

    public function testGetRow()
    {
        $table = new TableNode([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(['username', 'password'], $table->getRow(0));
        $this->assertEquals(['antono', 'pa$sword'], $table->getRow(2));
    }

    public function testGetColumn()
    {
        $table = new TableNode([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(['username', 'everzet', 'antono'], $table->getColumn(0));
        $this->assertEquals(['password', 'qwerty', 'pa$sword'], $table->getColumn(1));

        $table = new TableNode([
            ['username'],
            ['everzet'],
            ['antono'],
        ]);

        $this->assertEquals(['username', 'everzet', 'antono'], $table->getColumn(0));
    }

    public function testGetRowWithLineNumbers()
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(['username', 'password'], $table->getRow(0));
        $this->assertEquals(['antono', 'pa$sword'], $table->getRow(2));
    }

    public function testGetTable()
    {
        $table = new TableNode($a = [
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals($a, $table->getTable());
    }

    public function testGetRowLine()
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(5, $table->getRowLine(0));
        $this->assertEquals(13, $table->getRowLine(2));
    }

    public function testGetRowAsString()
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals('| username | password |', $table->getRowAsString(0));
        $this->assertEquals('| antono   | pa$sword |', $table->getRowAsString(2));
    }

    public function testGetTableAsString()
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $expected = <<<TABLE
| id | username | password |
| 42 | everzet  | qwerty   |
| 2  | antono   | pa\$sword |
TABLE;
        $this->assertEquals($expected, $table->getTableAsString());
    }

    public function testFromList()
    {
        $table = TableNode::fromList([
            'everzet',
            'antono',
        ]);

        $expected = new TableNode([
            ['everzet'],
            ['antono'],
        ]);
        $this->assertEquals($expected, $table);
    }

    public function testMergeRowsFromTablePassSeveralTablesShouldBeMerged()
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $new = new TableNode([
            25 => ['id', 'username', 'password'],
            210 => ['242', '2everzet', '2qwerty'],
            213 => ['22', '2antono', '2pa$sword'],
        ]);

        $new2 = new TableNode([
            35 => ['id', 'username', 'password'],
            310 => ['342', '3everzet', '3qwerty'],
            313 => ['32', '3antono', '3pa$sword'],
        ]);

        $table->mergeRowsFromTable($new);
        $table->mergeRowsFromTable($new2);

        $this->assertEquals(['id', 'username', 'password'], $table->getRow(0));
        $this->assertEquals(['2', 'antono', 'pa$sword'], $table->getRow(2));
        $this->assertEquals(['242', '2everzet', '2qwerty'], $table->getRow(3));
        $this->assertEquals(['32', '3antono', '3pa$sword'], $table->getRow(6));
    }

    public function testMergeRowsFromTableWrongHeaderNameExceptionThrown()
    {
        $this->expectException(NodeException::class);
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $new = new TableNode([
            25 => ['id', 'QWE', 'password'],
            210 => ['242', '2everzet', '2qwerty'],
        ]);

        $table->mergeRowsFromTable($new);
    }

    public function testGetTableFromListWithMultidimensionalArrayArgument()
    {
        $this->expectException(NodeException::class);
        TableNode::fromList([
            [1, 2, 3],
            [4, 5, 6],
        ]);
    }

    public function testMergeRowsFromTableWrongHeaderOrderExceptionThrown()
    {
        $this->expectException(NodeException::class);
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $new = new TableNode([
            25 => ['id', 'password', 'username'],
            210 => ['242', '2everzet', '2qwerty'],
        ]);

        $table->mergeRowsFromTable($new);
    }

    public function testMergeRowsFromTableWrongHeaderSizeExceptionThrown()
    {
        $this->expectException(NodeException::class);
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $new = new TableNode([
            25 => ['id', 'username'],
            210 => ['242', '2everzet'],
        ]);

        $table->mergeRowsFromTable($new);
    }
}
