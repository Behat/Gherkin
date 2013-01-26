<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\TableNode;

class TableNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testHashTable()
    {
        $table = new TableNode(<<<TABLE
| username | password |
| everzet  | qwerty   |
| antono   | pa\$sword|
TABLE
        );

        $this->assertEquals(
            array(
                array('username' => 'everzet', 'password' => 'qwerty')
              , array('username' => 'antono', 'password' => 'pa$sword')
            )
          , $table->getHash()
        );

        $table = new TableNode(<<<TABLE
| username | password |
|          | qwerty   |
| antono   |          |
|          |          |
TABLE
        );

        $this->assertEquals(
            array(
                array('username' => '', 'password' => 'qwerty')
              , array('username' => 'antono', 'password' => '')
              , array('username' => '', 'password' => '')
            )
          , $table->getHash()
        );
    }

    public function testRowsHashTable()
    {
        $table = new TableNode(<<<TABLE
| username | everzet  |
| password | qwerty   |
| uid      | 35       |
TABLE
        );

        $this->assertEquals(array('username' => 'everzet', 'password' => 'qwerty', 'uid' => '35'), $table->getRowsHash());
    }

    public function testLongRowsHashTable()
    {
        $table = new TableNode(<<<TABLE
| username | everzet  | marcello |
| password | qwerty   | 12345    |
| uid      | 35       | 22       |
TABLE
        );

        $this->assertEquals(array(
            'username' => array('everzet', 'marcello'),
            'password' => array('qwerty', '12345'),
            'uid'      => array('35', '22')
        ), $table->getRowsHash());
    }

    public function testTableFromArrayCreation()
    {
        $table1 = new TableNode();
        $table1->addRow(array('username', 'password'));
        $table1->addRow(array('everzet', 'qwerty'));
        $table1->addRow(array('antono', 'pa$sword'));

        $table2 = new TableNode(<<<TABLE
| username | password |
| everzet  | qwerty   |
| antono   | pa\$sword|
TABLE
        );

        $this->assertEquals($table2->getRows(), $table1->getRows());

        $this->assertEquals(
            array(
                array('username' => 'everzet', 'password' => 'qwerty')
              , array('username' => 'antono', 'password' => 'pa$sword')
            )
          , $table1->getHash()
        );

        $this->assertEquals(
            array('username' => 'password', 'everzet' => 'qwerty', 'antono' => 'pa$sword')
          , $table2->getRowsHash()
        );
    }

    public function testTokens()
    {
        $table = new TableNode();
        $table->addRow(array('username', 'password'));
        $table->addRow(array('<username>', '<password>'));

        $tableCompare = new TableNode(<<<TABLE
| username | password |
| everzet  | qwerty   |
TABLE
        );

        $exampleTable = $table->createExampleRowStepArgument(array(
            'username'=>'everzet',
            'password'=>'qwerty'
        ));
        $this->assertNotSame($table, $exampleTable);
        $this->assertSame($tableCompare->getRows(), $exampleTable->getRows());
    }
}
