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

    public function testTokens()
    {
        $string = new PyStringNode();
        $string->addLine('Hello, <username>');
        $string->addLine('everything is <status>?');

        $string1 = $string->createExampleRowStepArgument(array('username'=>'John', 'status'=>'ok'));
        $this->assertNotSame($string, $string1);
        $this->assertSame(<<<STRING
Hello, John
everything is ok?
STRING
          , (string) $string1
        );

        $string2 = $string->createExampleRowStepArgument(array('username'=>'Mike', 'status'=>'bad'));
        $this->assertNotSame($string, $string2);
        $this->assertSame(<<<STRING
Hello, Mike
everything is bad?
STRING
          , (string) $string2
        );
    }
}
