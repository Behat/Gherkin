<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $gherkin;
    private YamlFileLoader $yaml;

    /**
     * @return iterable<string, array{fixtureName: string}>
     */
    public static function parserTestDataProvider(): iterable
    {
        foreach (glob(__DIR__ . '/Fixtures/etalons/*.yml') as $file) {
            $testname = basename($file, '.yml');
            yield $testname => ['fixtureName' => $testname];
        }
    }

    #[DataProvider('parserTestDataProvider')]
    public function testParser(string $fixtureName): void
    {
        $etalon = $this->parseEtalon($fixtureName . '.yml');
        $fixture = $this->parseFixture($fixtureName . '.feature');

        $this->assertEquals($etalon, $fixture);
    }

    public function testParserResetsTagsBetweenFeatures(): void
    {
        $parser = $this->getGherkinParser();

        $parser->parse(<<<'FEATURE'
        Feature:
        Scenario:
        Given step
        @skipped
        FEATURE
        );
        $feature2 = $parser->parse(<<<'FEATURE'
        Feature:
        Scenario:
        Given step
        FEATURE
        );

        $this->assertFalse($feature2->hasTags());
    }

    public function testSingleCharacterStepSupport(): void
    {
        $feature = $this->getGherkinParser()->parse(<<<'FEATURE'
        Feature:
        Scenario:
        When x
        FEATURE
        );

        $scenarios = $feature->getScenarios();
        $scenario = array_shift($scenarios);

        $this->assertCount(1, $scenario->getSteps());
    }

    protected function getGherkinParser()
    {
        return $this->gherkin ??= new Parser(
            new Lexer(
                new ArrayKeywords([
                    'en' => [
                        'feature' => 'Feature',
                        'background' => 'Background',
                        'scenario' => 'Scenario',
                        'scenario_outline' => 'Scenario Outline',
                        'examples' => 'Examples',
                        'given' => 'Given',
                        'when' => 'When',
                        'then' => 'Then',
                        'and' => 'And',
                        'but' => 'But',
                    ],
                    'ru' => [
                        'feature' => 'Функционал',
                        'background' => 'Предыстория',
                        'scenario' => 'Сценарий',
                        'scenario_outline' => 'Структура сценария',
                        'examples' => 'Примеры',
                        'given' => 'Допустим',
                        'when' => 'То',
                        'then' => 'Если',
                        'and' => 'И',
                        'but' => 'Но',
                    ],
                    'ja' => [
                        'feature' => 'フィーチャ',
                        'background' => '背景',
                        'scenario' => 'シナリオ',
                        'scenario_outline' => 'シナリオアウトライン',
                        'examples' => '例|サンプル',
                        'given' => '前提<',
                        'when' => 'もし<',
                        'then' => 'ならば<',
                        'and' => 'かつ<',
                        'but' => 'しかし<',
                    ],
                ])
            )
        );
    }

    protected function getYamlParser(): YamlFileLoader
    {
        return $this->yaml ??= new YamlFileLoader();
    }

    protected function parseFixture(string $fixture): ?FeatureNode
    {
        $file = __DIR__ . '/Fixtures/features/' . $fixture;

        return $this->getGherkinParser()->parse(file_get_contents($file), $file);
    }

    protected function parseEtalon($etalon): FeatureNode
    {
        $features = $this->getYamlParser()->load(__DIR__ . '/Fixtures/etalons/' . $etalon);
        $feature = $features[0];

        return new FeatureNode(
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getTags(),
            $feature->getBackground(),
            $feature->getScenarios(),
            $feature->getKeyword(),
            $feature->getLanguage(),
            __DIR__ . '/Fixtures/features/' . basename($etalon, '.yml') . '.feature',
            $feature->getLine()
        );
    }

    public function testParsingManyCommentsShouldPass(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('xdebug extension must be enabled.');
        }
        $defaultPHPSetting = 256;
        ini_set('xdebug.max_nesting_level', $defaultPHPSetting);

        $lineCount = 150; // 119 is the real threshold, higher just in case
        $this->assertNull($this->getGherkinParser()->parse(str_repeat("# \n", $lineCount)));
    }
}
