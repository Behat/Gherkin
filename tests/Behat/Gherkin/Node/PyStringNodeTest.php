<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\PyStringNode;

class PyStringNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testPyString()
    {
        $string = new PyStringNode(<<<STRING
Hello,
  Gherkin
    users
      =)
STRING
        );

        $this->assertEquals(<<<STRING
Hello,
  Gherkin
    users
      =)
STRING
          , (string) $string
        );

        $string = new PyStringNode(<<<STRING
Hello,
 Gherkin
    users
      =)
STRING
        );

        $this->assertEquals(<<<STRING
Hello,
 Gherkin
    users
      =)
STRING
          , (string) $string
        );
    }

    public function testPyStringFromLinesCreation()
    {
        $string = new PyStringNode();
        $string->addLine('Hello,');
        $string->addLine('  Gherkin');
        $string->addLine('    users');
        $string->addLine('      =)');

        $this->assertEquals(<<<STRING
Hello,
  Gherkin
    users
      =)
STRING
          , (string) $string
        );
    }
}
