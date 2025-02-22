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
use Behat\Gherkin\Node\StepNode;

class CachedArrayKeywordsTest extends KeywordsTestCase
{
    protected function getKeywords()
    {
        return new CachedArrayKeywords(__DIR__ . '/../../i18n.php');
    }

    protected function getKeywordsArray()
    {
        return include __DIR__ . '/../../i18n.php';
    }

    protected function getSteps($keywords, $text, &$line, $keywordType)
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
