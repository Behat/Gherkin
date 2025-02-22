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
    public function testGetStrings(): void
    {
        $str = new PyStringNode(['line1', 'line2', 'line3'], 1);

        $this->assertEquals(['line1', 'line2', 'line3'], $str->getStrings());
    }

    public function testGetRaw(): void
    {
        $str = new PyStringNode(['line1', 'line2', 'line3'], 1);

        $expected = <<<'STR'
        line1
        line2
        line3
        STR;
        $this->assertEquals($expected, $str->getRaw());
    }
}
