<?php
namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\ExampleTableNode;

class ExampleTableNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test getCleanRows from ExampleTableNode
     * @see Behat\Gherkin\Node\ExampleTableNode::getCleanRows()
     */
    public function testExampleTableNodeGetCleanRows()
    {
        $table = new TableNode(<<<TABLE
| <field1> | <value1> |
| <field2> | <value2> |
| test     | 123      |
TABLE
        );

        $tokens = array(
            "field1" => "lorem",
            "value1" => "ipsum",
            "field1" => "val",
            "value1" => "321"
        );

        $exampleTable = new ExampleTableNode( $table, $tokens );

        $this->assertEquals(
            array(
                array( "<field1>", "<value1>" ),
                array( "<field2>", "<value2>" ),
                array( "test", "123" ),
            ),
            $exampleTable->getCleanRows()
        );
    }
}
