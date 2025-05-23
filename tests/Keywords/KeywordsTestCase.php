<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsDumper;
use Behat\Gherkin\Keywords\KeywordsInterface;
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
 * @phpstan-import-type TMultiLanguageKeywords from ArrayKeywords
 */
abstract class KeywordsTestCase extends TestCase
{
    abstract protected static function getKeywords(): KeywordsInterface;

    /**
     * @phpstan-return TMultiLanguageKeywords
     */
    abstract protected static function getKeywordsArray(): array;

    /**
     * @return list<StepNode>
     */
    abstract protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array;

    /**
     * @return iterable<string, array{language: string, num: int, etalon: FeatureNode, source: string}>
     */
    public static function translationTestDataProvider(): iterable
    {
        $keywords = static::getKeywords();
        $dumper = new KeywordsDumper($keywords);
        $keywordsArray = static::getKeywordsArray();

        // Remove languages with repeated keywords
        unset($keywordsArray['en-old'], $keywordsArray['uz'], $keywordsArray['ne']);

        $data = [];
        foreach ($keywordsArray as $lang => $i18nKeywords) {
            $features = [];
            foreach (explode('|', $i18nKeywords['feature']) as $transNum => $featureKeyword) {
                $line = 1;
                if ($lang !== 'en') {
                    $line = 2;
                }

                $featureLine = $line;
                $line += 5;

                $keywords = explode('|', $i18nKeywords['background']);
                $backgroundLine = $line;
                ++$line;
                $background = new BackgroundNode(null, array_merge(
                    static::getSteps($i18nKeywords['given'], 'there is agent A', $line, 'Given'),
                    static::getSteps($i18nKeywords['and'], 'there is agent B', $line, 'Given')
                ), $keywords[0], $backgroundLine);

                ++$line;

                $scenarios = [];

                foreach (explode('|', $i18nKeywords['scenario']) as $scenarioKeyword) {
                    $scenarioLine = $line;
                    ++$line;

                    $steps = array_merge(
                        static::getSteps($i18nKeywords['given'], 'there is agent J', $line, 'Given'),
                        static::getSteps($i18nKeywords['and'], 'there is agent K', $line, 'Given'),
                        static::getSteps($i18nKeywords['when'], 'I erase agent K\'s memory', $line, 'When'),
                        static::getSteps($i18nKeywords['then'], 'there should be agent J', $line, 'Then'),
                        static::getSteps($i18nKeywords['but'], 'there should not be agent K', $line, 'Then')
                    );

                    $scenarios[] = new ScenarioNode('Erasing agent memory', [], $steps, $scenarioKeyword, $scenarioLine);
                    ++$line;
                }
                foreach (explode('|', $i18nKeywords['scenario_outline']) as $outlineKeyword) {
                    $outlineLine = $line;
                    ++$line;

                    $steps = array_merge(
                        static::getSteps($i18nKeywords['given'], 'there is agent <agent1>', $line, 'Given'),
                        static::getSteps($i18nKeywords['and'], 'there is agent <agent2>', $line, 'Given'),
                        static::getSteps($i18nKeywords['when'], 'I erase agent <agent2>\'s memory', $line, 'When'),
                        static::getSteps($i18nKeywords['then'], 'there should be agent <agent1>', $line, 'Then'),
                        static::getSteps($i18nKeywords['but'], 'there should not be agent <agent2>', $line, 'Then')
                    );
                    ++$line;

                    $keywords = explode('|', $i18nKeywords['examples']);
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

        return $data;
    }

    /**
     * @param string $language language name
     * @param int $num Fixture index for that language
     * @param FeatureNode $etalon etalon features (to test against)
     * @param string $source gherkin source
     */
    #[DataProvider('translationTestDataProvider')]
    public function testTranslation(string $language, int $num, FeatureNode $etalon, string $source): void
    {
        $keywords = static::getKeywords();
        $lexer = new Lexer($keywords);
        $parser = new Parser($lexer);

        try {
            $parsed = $parser->parse($source, __DIR__ . DIRECTORY_SEPARATOR . $language . '_' . ($num + 1) . '.feature');
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage() . ":\n" . $source, 0, $e);
        }

        $this->assertEquals($etalon, $parsed);
    }
}
