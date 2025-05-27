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
use RuntimeException;

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
        return array_map(
            static fn ($dialect) => is_array($dialect)
                ? [
                    'feature' => implode('|', (array) ($dialect['feature'] ?? [])),
                    'background' => implode('|', (array) ($dialect['background'] ?? [])),
                    'scenario' => implode('|', (array) ($dialect['scenario'] ?? [])),
                    'scenario_outline' => implode('|', (array) ($dialect['scenarioOutline'] ?? [])),
                    'examples' => implode('|', (array) ($dialect['examples'] ?? [])),
                    'given' => implode('|', (array) ($dialect['given'] ?? [])),
                    'when' => implode('|', (array) ($dialect['when'] ?? [])),
                    'then' => implode('|', (array) ($dialect['then'] ?? [])),
                    'and' => implode('|', (array) ($dialect['and'] ?? [])),
                    'but' => implode('|', (array) ($dialect['but'] ?? [])),
                ]
                : throw new RuntimeException('Invalid data; expected array of keywords but got: ' . get_debug_type($dialect)),
            Filesystem::readJsonFileHash(__DIR__ . '/../../resources/gherkin-languages.json'),
        );
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
