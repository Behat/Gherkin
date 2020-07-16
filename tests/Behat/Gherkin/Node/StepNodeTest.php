<?php

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase;

class StepNodeTest extends TestCase
{
    public function testThatStepCanHaveOnlyOneArgument()
    {
        $this->expectException('Behat\Gherkin\Exception\NodeException');

        new StepNode('Gangway!', 'I am on the page:', array(
            new PyStringNode(array('one', 'two'), 11),
            new TableNode(array(array('one', 'two'))),
        ), 10, 'Given');
    }
}
