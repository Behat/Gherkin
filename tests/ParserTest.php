<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Parser;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

final class ParserTest extends TestCase
{
    /**
     * @return iterable<string, array{fixtureName: string}>
     */
    public static function parserTestDataProvider(): iterable
    {
        foreach ((new Finder())->in(__DIR__ . '/Fixtures/etalons')->name('*.yml') as $file) {
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
        $parser = $this->createGherkinParser();

        $parser->parse(
            <<<'FEATURE'
            Feature:
            Scenario:
            Given step
            @skipped
            FEATURE
        );
        $feature2 = $parser->parse(
            <<<'FEATURE'
            Feature:
            Scenario:
            Given step
            FEATURE
        );

        $this->assertInstanceOf(FeatureNode::class, $feature2);
        $this->assertSame([], $feature2->getTags());
    }

    public function testSingleCharacterStepSupport(): void
    {
        $feature = $this->createGherkinParser()->parse(
            <<<'FEATURE'
            Feature:
            Scenario:
            When x
            FEATURE
        );

        $this->assertInstanceOf(FeatureNode::class, $feature);
        $scenarios = $feature->getScenarios();
        $scenario = array_shift($scenarios);

        $this->assertInstanceOf(ScenarioNode::class, $scenario);
        $this->assertCount(1, $scenario->getSteps());
    }

    public function testParsingManyCommentsShouldPass(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('xdebug extension must be enabled.');
        }

        $oldMaxNestingLevel = ini_set('xdebug.max_nesting_level', 256);
        if ($oldMaxNestingLevel === false) {
            throw new \RuntimeException('Could not set INI setting value');
        }

        try {
            $lineCount = 150; // 119 is the real threshold, higher just in case
            $this->assertNull($this->createGherkinParser()->parse(str_repeat("# \n", $lineCount)));
        } finally {
            ini_set('xdebug.max_nesting_level', $oldMaxNestingLevel);
        }
    }

    #[DataProvider('parserErrorDataProvider')]
    public function testParserError(Exception $expectedException, string $featureText): void
    {
        $this->expectExceptionObject($expectedException);

        $this->createGherkinParser()->parse($featureText, '/fake.feature');
    }

    /**
     * @return iterable<array{expectedException: Exception, featureText: string|list<array<string, mixed>>}>
     */
    public static function parserErrorDataProvider(): iterable
    {
        yield 'missing feature' => [
            'expectedException' => new ParserException('Expected Feature, but got Scenario on line: 1 in file: /fake.feature'),
            'featureText' => <<<'FEATURE'
            Scenario: nope
            FEATURE,
        ];

        yield 'invalid content encoding' => [
            'expectedException' => new ParserException('Lexer exception "Feature file is not in UTF8 encoding" thrown for file /fake.feature'),
            'featureText' => mb_convert_encoding('🔥 Все буде добре 🔥', 'EUC-JP', 'UTF-8'),
        ];

        yield 'text content in background' => [
            'expectedException' => new ParserException('Expected Step, but got text: "    nope" in file: /fake.feature'),
            'featureText' => <<<'FEATURE'
            Feature:
              Background:
                Given I do something
                nope
            FEATURE,
        ];

        yield 'text content in outline' => [
            'expectedException' => new ParserException('Expected Step or Examples table, but got text: "    nope" in file: /fake.feature'),
            'featureText' => <<<'FEATURE'
            Feature:
              Scenario Outline:
                Given I do something
                nope
            FEATURE,
        ];

        yield 'invalid outline examples table' => [
            'expectedException' => new ParserException('Table row \'1\' is expected to have 2 columns, got 1 in file /fake.feature'),
            'featureText' => <<<'FEATURE'
            Feature:
              Scenario Outline:
                Given I do something
                Examples:
                | aaaa | bbbb |
                | cccc   cccc |
            FEATURE,
        ];
    }

    private function createGherkinParser(?Lexer $lexer = null): Parser
    {
        return new Parser($lexer ?? new Lexer($this->createKeywords()));
    }

    private function createKeywords(): KeywordsInterface
    {
        return new ArrayKeywords([
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
        ]);
    }

    private function createYamlParser(): YamlFileLoader
    {
        return new YamlFileLoader();
    }

    private function parseFixture(string $fixture): ?FeatureNode
    {
        $file = __DIR__ . "/Fixtures/features/$fixture";

        return $this->createGherkinParser()->parse(Filesystem::readFile($file), $file);
    }

    private function parseEtalon(string $etalon): FeatureNode
    {
        $features = $this->createYamlParser()->load(__DIR__ . '/Fixtures/etalons/' . $etalon);
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
}
