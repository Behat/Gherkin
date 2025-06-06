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
    public function testConstructorExpectsSameNumberOfColumnsInEachRow(): void
    {
        $this->expectExceptionObject(
            new NodeException("Table row '1' is expected to have 2 columns, got 1")
        );

        new TableNode([
            ['username', 'password'],
            ['everzet'],
            ['antono', 'pa$sword'],
        ]);
    }

    public function testConstructorExpectsTwoDimensionalArray(): void
    {
        $this->expectExceptionObject(
            new NodeException("Table row '0' is expected to be array, got string")
        );

        // @phpstan-ignore argument.type (we are explicitly testing an invalid instantiation)
        new TableNode([
            'everzet', 'antono',
        ]);
    }

    public function testConstructorExpectsScalarCellValue(): void
    {
        $this->expectExceptionObject(
            new NodeException("Table cell at row '0', column '0' is expected to be scalar, got array")
        );

        // @phpstan-ignore argument.type (we are explicitly testing an invalid instantiation)
        new TableNode([
            [['everzet', 'antono']],
        ]);
    }

    public function testConstructorExpectsEqualRowLengths(): void
    {
        $this->expectExceptionObject(
            new NodeException("Table row '1' is expected to have 2 columns, got 1")
        );

        new TableNode([
            ['everzet', 'antono'],
            ['everzet'],
        ]);
    }

    public function testHashTable(): void
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

    public function testIterator(): void
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

    public function testRowsHashTable(): void
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

    public function testLongRowsHashTable(): void
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

    public function testGetRows(): void
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

    public function testGetLines(): void
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals([5, 10, 13], $table->getLines());
    }

    public function testGetRow(): void
    {
        $table = new TableNode([
            ['username', 'password'],
            ['everzet', 'qwerty'],
            ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(['username', 'password'], $table->getRow(0));
        $this->assertEquals(['antono', 'pa$sword'], $table->getRow(2));
    }

    public function testGetColumn(): void
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

    public function testGetRowWithLineNumbers(): void
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(['username', 'password'], $table->getRow(0));
        $this->assertEquals(['antono', 'pa$sword'], $table->getRow(2));
    }

    public function testGetTable(): void
    {
        $table = new TableNode($a = [
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals($a, $table->getTable());
    }

    public function testGetRowLine(): void
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals(5, $table->getRowLine(0));
        $this->assertEquals(13, $table->getRowLine(2));
    }

    public function testGetRowAsString(): void
    {
        $table = new TableNode([
            5 => ['username', 'password'],
            10 => ['everzet', 'qwerty'],
            13 => ['antono', 'pa$sword'],
        ]);

        $this->assertEquals('| username | password |', $table->getRowAsString(0));
        $this->assertEquals('| antono   | pa$sword |', $table->getRowAsString(2));
    }

    public function testGetTableAsString(): void
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);

        $expected = <<<'TABLE'
        | id | username | password |
        | 42 | everzet  | qwerty   |
        | 2  | antono   | pa$sword |
        TABLE;
        $this->assertEquals($expected, $table->getTableAsString());
    }

    public function testFromList(): void
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

    public function testFromListWithLineNumbers(): void
    {
        $table = TableNode::fromList([
            12 => 'everzet',
            15 => 'antono',
        ]);

        $expected = new TableNode([
            12 => ['everzet'],
            15 => ['antono'],
        ]);
        $this->assertEquals($expected, $table);
    }

    public function testMergeRowsFromTablePassSeveralTablesShouldBeMerged(): void
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

    public function testMergeRowsFromTableWrongHeaderNameExceptionThrown(): void
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);
        $new = new TableNode([
            25 => ['id', 'QWE', 'password'],
            210 => ['242', '2everzet', '2qwerty'],
        ]);

        $this->expectExceptionObject(
            new NodeException('Tables have different structure. Cannot merge one into another')
        );

        $table->mergeRowsFromTable($new);
    }

    public function testGetTableFromListWithMultidimensionalArrayArgument(): void
    {
        $this->expectExceptionObject(
            new NodeException('List is not a one-dimensional array.')
        );

        // @phpstan-ignore argument.type (we are explicitly testing an invalid instantiation)
        TableNode::fromList([
            ['1', '2', '3'],
            ['4', '5', '6'],
        ]);
    }

    public function testMergeRowsFromTableWrongHeaderOrderExceptionThrown(): void
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);
        $new = new TableNode([
            25 => ['id', 'password', 'username'],
            210 => ['242', '2everzet', '2qwerty'],
        ]);

        $this->expectExceptionObject(
            new NodeException('Tables have different structure. Cannot merge one into another')
        );

        $table->mergeRowsFromTable($new);
    }

    public function testMergeRowsFromTableWrongHeaderSizeExceptionThrown(): void
    {
        $table = new TableNode([
            5 => ['id', 'username', 'password'],
            10 => ['42', 'everzet', 'qwerty'],
            13 => ['2', 'antono', 'pa$sword'],
        ]);
        $new = new TableNode([
            25 => ['id', 'username'],
            210 => ['242', '2everzet'],
        ]);

        $this->expectExceptionObject(
            new NodeException('Tables have different structure. Cannot merge one into another')
        );

        $table->mergeRowsFromTable($new);
    }
}
