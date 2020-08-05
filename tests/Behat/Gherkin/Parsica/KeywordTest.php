<?php
declare(strict_types=1);

namespace Tests\Behat\Gherkin\Parsica;

use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;
use function Behat\Gherkin\Parsica\keyword;

class KeywordTest extends TestCase
{
    use ParserAssertions;

    /** @test */
    function it_parses_a_keyword()
    {
        $input = 'Foo ';
        $expected = 'Foo';
        $parser = keyword('Foo', false);

        $this->assertParses($input, $parser, $expected);
    }

    /** @test */
    function it_parses_a_keyword_followed_by_colon()
    {
        $input = 'Foo:';
        $expected = 'Foo';
        $parser = keyword('Foo', true);

        $this->assertParses($input, $parser, $expected);
    }

    /** @test */
    function it_allows_prefixed_whitespace()
    {
        $input = "\t   Foo ";
        $expected = 'Foo';
        $parser = keyword('Foo', false);

        $this->assertParses($input, $parser, $expected);
    }
}
