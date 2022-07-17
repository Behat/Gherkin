<?php declare(strict_types=1);

namespace Behat\Gherkin\Cucumber;

use Cucumber\Messages\Location;

final class MultilineStringFormatter
{
    public static function format(string $string, Location $keywordLocation = null): string
    {
        if (!$keywordLocation) {
            $keywordLocation = new Location(0,1);
        }

        $maxIndent = ($keywordLocation->column-1 ?: 0) + 2;

        return preg_replace(
            ["/^[^\n\S]{0,$maxIndent}/um", '/[^\n\S]+$/um'],
            ['', ''],
            $string
        );
    }
}
