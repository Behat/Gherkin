<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CucumberKeywords;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\Yaml\Yaml;

class CucumberKeywordsTest extends KeywordsTestCase
{
    protected function getKeywords()
    {
        return new CucumberKeywords(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected function getKeywordsArray()
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/../Fixtures/i18n.yml'));
    }

    protected function getSteps($keywords, $text, &$line, $keywordType)
    {
        $steps = [];
        foreach (explode('|', mb_substr($keywords, 2)) as $keyword) {
            if (mb_strpos($keyword, '<') !== false) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, [], $line++, $keywordType);
        }

        return $steps;
    }
}
