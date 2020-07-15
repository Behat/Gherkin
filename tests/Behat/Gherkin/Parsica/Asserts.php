<?php
declare(strict_types=1);

namespace Behat\Gherkin\Parsica;

use Verraes\Parsica\Parser;

/** @todo upgrade phpunit and use Parsica traits */
trait Asserts
{
    private function assertParse($expected, Parser $parser, string $input)
    {
        $actual = $parser->tryString("$input")->output();

        $this->assertEquals($expected, $actual);
    }
}
