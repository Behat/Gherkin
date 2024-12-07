<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\TestCase;

class PyStringNodeTest extends TestCase
{
    public function testGetStrings()
    {
        $str = new PyStringNode(['line1', 'line2', 'line3'], 0);

        $this->assertEquals(['line1', 'line2', 'line3'], $str->getStrings());
    }

    public function testGetRaw()
    {
        $str = new PyStringNode(['line1', 'line2', 'line3'], 0);

        $expected = <<<STR
line1
line2
line3
STR;
        $this->assertEquals($expected, $str->getRaw());
    }
}
