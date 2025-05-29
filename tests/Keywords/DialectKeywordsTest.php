<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Filesystem;
use Behat\Gherkin\Keywords\DialectKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Node\StepNode;

class DialectKeywordsTest extends KeywordsTestCase
{
    public function testFailsForEmptyLanguage(): void
    {
        $keywords = new DialectKeywords(new CucumberDialectProvider());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Language cannot be empty');

        $keywords->setLanguage('');
    }

    protected static function getKeywords(): KeywordsInterface
    {
        return new DialectKeywords(new CucumberDialectProvider());
    }

    protected static function getKeywordsArray(): array
    {
        $data = Filesystem::readJsonFile(__DIR__ . '/../../resources/gherkin-languages.json', true);

        $keywordsArray = [];

        foreach ($data as $lang => $dialect) {
            $keywordsArray[$lang] = [
                'feature' => implode('|', $dialect['feature']),
                'background' => implode('|', $dialect['background']),
                'scenario' => implode('|', $dialect['scenario']),
                'scenario_outline' => implode('|', $dialect['scenarioOutline']),
                'examples' => implode('|', $dialect['examples']),
                'given' => implode('|', $dialect['given']),
                'when' => implode('|', $dialect['when']),
                'then' => implode('|', $dialect['then']),
                'and' => implode('|', $dialect['and']),
                'but' => implode('|', $dialect['but']),
            ];
        }

        return $keywordsArray;
    }

    protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach (explode('|', $keywords) as $keyword) {
            if ($keyword === '* ') {
                continue;
            }

            $steps[] = new StepNode(trim($keyword), $text, [], $line++, $keywordType);
        }

        return $steps;
    }
}
