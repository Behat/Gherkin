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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase;

class StepNodeTest extends TestCase
{
    public function testThatStepCanHaveOnlyOneArgument(): void
    {
        $this->expectException(NodeException::class);

        new StepNode('Gangway!', 'I am on the page:', [
            new PyStringNode(['one', 'two'], 11),
            new TableNode([['one', 'two']]),
        ], 10, 'Given');
    }
}
