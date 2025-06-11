<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CachedArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Node\StepNode;

class CachedArrayKeywordsTest extends KeywordsTestCase
{
    protected static function getKeywords(): KeywordsInterface
    {
        // Test with the default i18n file provided in this repository
        return CachedArrayKeywords::withDefaultKeywords();
    }

    protected static function getKeywordsArray(): array
    {
        // @phpstan-ignore return.type
        return require __DIR__ . '/../../i18n.php';
    }

    protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach (explode('|', $keywords) as $keyword) {
            if ($keyword === '*') {
                continue;
            }

            if (str_contains($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, [], $line++, $keywordType);
        }

        return $steps;
    }
}
