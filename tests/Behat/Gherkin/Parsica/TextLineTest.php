<?php
declare(strict_types=1);

namespace Tests\Behat\Parsica;

use Behat\Gherkin\Parsica\Asserts;
use Behat\Parsica;
use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;
use Verraes\Parsica\StringStream;
use function Behat\Gherkin\Parsica\textLine;

class TextLineTest extends TestCase
{
    use ParserAssertions;
    
    /** 
     * @test 
     * @dataProvider examples
     */
    public function it_parses_textlines(string $input, string $expected, string $expectedRemainder)
    {
        $parser = textLine();

        $this->assertParses($input, $parser, $expected);

        $remainder = (string)$parser->tryString($input)->remainder();
        $this->assertEquals($expectedRemainder, $remainder);
    }

    public static function examples() : iterable
    {
        yield 'empty' => ['', '', ''];
        yield 'without whitespace' => ['blah blah', 'blah blah', ''];
        yield 'whitespace only' => ["    \t    ", '', ''];
        yield 'leading whitespace' => ['  blah blah', 'blah blah', ''];
        yield 'trailing whitespace' => ['blah blah    ', 'blah blah', ''];
        yield 'with linebreak' => ["blah blah blah         \t   \n", "blah blah blah", ''];
        yield 'with linebreak and following text' => ["blah blah blah         \t   \nfoo", "blah blah blah", 'foo'];
    }

    /**
     * @test
     * @dataProvider badExamples
     * @dataProvider notSupportedYetExamples
     */
    public function it_fails_on_bad_textlines(string $input)
    {
        $parser = textLine();

        $result = $parser->run(new StringStream($input));
        
        $this->assertTrue($result->isFail());
    }

    public static function badExamples() : iterable
    {
        yield 'with null byte' => ["blah blah blah         \0   "];
        yield 'with vertical tab' => ["blah blah blah         \x0B   "];
        yield 'with non-breaking space' => ["blah blah blah         \xA0   "];
    }

    /** @todo make this work */
    public static function notSupportedYetExamples() : iterable
    {
        yield 'with 🥰 and text' => ["blah 🥰 blah"];
    }
}

