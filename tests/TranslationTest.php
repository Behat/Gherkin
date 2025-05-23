<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Dialect\GherkinDialect;
use Behat\Gherkin\Keywords\DialectKeywords;
use Behat\Gherkin\Keywords\KeywordsDumper;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type TDialectData from GherkinDialect
 */
class TranslationTest extends TestCase
{
    /**
     * @param list<string> $keywords
     *
     * @return list<StepNode>
     */
    private static function getSteps(array $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach ($keywords as $keyword) {
            if ($keyword === '* ') {
                continue;
            }

            $steps[] = new StepNode(trim($keyword), $text, [], $line++, $keywordType);
        }

        return $steps;
    }

    /**
     * @return iterable<string, array{language: string, num: int, etalon: FeatureNode, source: string}>
     */
    public static function translationTestDataProvider(): iterable
    {
        $dumper = new KeywordsDumper(new DialectKeywords(new CucumberDialectProvider()));
        /** @var non-empty-array<non-empty-string, TDialectData> $keywordsArray */
        $keywordsArray = json_decode(Filesystem::readFile(__DIR__ . '/../resources/gherkin-languages.json'), true, flags: \JSON_THROW_ON_ERROR);

        foreach ($keywordsArray as $lang => $i18nKeywords) {
            $features = [];
            foreach ($i18nKeywords['feature'] as $transNum => $featureKeyword) {
                $line = 1;
                if ($lang !== 'en') {
                    $line = 2;
                }

                $featureLine = $line;
                $line += 5;

                $keywords = $i18nKeywords['background'];
                $backgroundLine = $line;
                ++$line;
                $background = new BackgroundNode(null, array_merge(
                    self::getSteps($i18nKeywords['given'], 'there is agent A', $line, 'Given'),
                    self::getSteps($i18nKeywords['and'], 'there is agent B', $line, 'Given')
                ), $keywords[0], $backgroundLine);

                ++$line;

                $scenarios = [];

                foreach ($i18nKeywords['scenario'] as $scenarioKeyword) {
                    $scenarioLine = $line;
                    ++$line;

                    $steps = array_merge(
                        self::getSteps($i18nKeywords['given'], 'there is agent J', $line, 'Given'),
                        self::getSteps($i18nKeywords['and'], 'there is agent K', $line, 'Given'),
                        self::getSteps($i18nKeywords['when'], 'I erase agent K\'s memory', $line, 'When'),
                        self::getSteps($i18nKeywords['then'], 'there should be agent J', $line, 'Then'),
                        self::getSteps($i18nKeywords['but'], 'there should not be agent K', $line, 'Then')
                    );

                    $scenarios[] = new ScenarioNode('Erasing agent memory', [], $steps, $scenarioKeyword, $scenarioLine);
                    ++$line;
                }
                foreach ($i18nKeywords['scenarioOutline'] as $outlineKeyword) {
                    $outlineLine = $line;
                    ++$line;

                    $steps = array_merge(
                        self::getSteps($i18nKeywords['given'], 'there is agent <agent1>', $line, 'Given'),
                        self::getSteps($i18nKeywords['and'], 'there is agent <agent2>', $line, 'Given'),
                        self::getSteps($i18nKeywords['when'], 'I erase agent <agent2>\'s memory', $line, 'When'),
                        self::getSteps($i18nKeywords['then'], 'there should be agent <agent1>', $line, 'Then'),
                        self::getSteps($i18nKeywords['but'], 'there should not be agent <agent2>', $line, 'Then')
                    );
                    ++$line;

                    $keywords = $i18nKeywords['examples'];
                    $table = new ExampleTableNode([
                        ++$line => ['agent1', 'agent2'],
                        ++$line => ['D', 'M'],
                    ], $keywords[0]);
                    ++$line;

                    $scenarios[] = new OutlineNode('Erasing other agents\' memory', [], $steps, $table, $outlineKeyword, $outlineLine);
                    ++$line;
                }

                $features[] = new FeatureNode(
                    'Internal operations',
                    <<<'DESC'
                    In order to stay secret
                    As a secret organization
                    We need to be able to erase past agents' memory
                    DESC,
                    [],
                    $background,
                    $scenarios,
                    $featureKeyword,
                    $lang,
                    __DIR__ . DIRECTORY_SEPARATOR . $lang . '_' . ($transNum + 1) . '.feature',
                    $featureLine
                );
            }

            $dumped = $dumper->dump($lang, false, true);

            foreach ($dumped as $num => $dumpedFeature) {
                yield $lang . '_' . $num => [
                    'language' => $lang,
                    'num' => $num,
                    'etalon' => $features[$num],
                    'source' => $dumpedFeature,
                ];
            }
        }
    }

    #[DataProvider('translationTestDataProvider')]
    public function testTranslation(string $language, int $num, FeatureNode $etalon, string $source): void
    {
        $lexer = new Lexer(new CucumberDialectProvider());
        $parser = new Parser($lexer);

        try {
            $parsed = $parser->parse($source, __DIR__ . DIRECTORY_SEPARATOR . $language . '_' . ($num + 1) . '.feature');
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage() . ":\n" . $source, 0, $e);
        }

        $this->assertEquals($etalon, $parsed, $source);
    }
}
