<?php declare(strict_types=1);

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\Location;
use PHPUnit\Framework\TestCase;

/** @group cucumber */
final class MultilineStringFormatterTest extends TestCase
{
    public function testItLeftTrimsAString()
    {
        $input = <<<EOF
  foo
  bar
EOF;
        $expected = <<<EOF
foo
bar
EOF;

        self::assertSame($expected, MultilineStringFormatter::format($input));
    }

    public function testItRightTrimsAString()
    {
        $input = "foo   \nbar   ";
        $expected = "foo\nbar";

        self::assertSame($expected, MultilineStringFormatter::format($input));
    }

    public function testItDoesNotLeftTrimAStringMoreThanTwoLinesPastIndent()
    {
        $input = <<<EOF
      foo
      bar
EOF;
        $expected = <<<EOF
  foo
  bar
EOF;

        self::assertSame($expected, MultilineStringFormatter::format($input, new Location(0,3)));
    }
}
