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
use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Parser;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ParserTest extends TestCase
{
    public function testParserResetsTagsBetweenFeatures(): void
    {
        $parser = $this->createGherkinParser();

        try {
            $parser->parse(
                <<<'FEATURE'
                Feature:
                Scenario:
                Given step
                @skipped
                FEATURE,
            );
        } catch (ParserException $e) {
            // expected - features cannot end with tags
            $this->assertSame('Unexpected end of file after tags on line: 5', $e->getMessage());
        }
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

    public function testParserIgnoresInvalidLanguageInLegacyMode(): void
    {
        $feature = $this->createGherkinParser()->parse(
            <<<'FEATURE'
            #language:no-such

            Feature: Minimal

              Scenario: minimalistic
                Given the minimalism
            FEATURE,
        );

        $this->assertInstanceOf(FeatureNode::class, $feature);
        $this->assertCount(1, $feature->getScenarios());
    }

    public function testParserIgnoresInvalidLanguageInLegacyModeWithDialectProvider(): void
    {
        $feature = $this->createGherkinParser(new Lexer(new CucumberDialectProvider()))->parse(
            <<<'FEATURE'
            #language:no-such

            Feature: Minimal

              Scenario: minimalistic
                Given the minimalism
            FEATURE,
        );

        $this->assertInstanceOf(FeatureNode::class, $feature);
        $this->assertCount(1, $feature->getScenarios());
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
            throw new RuntimeException('Could not set INI setting value');
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

    public function testInexistentFileParserError(): void
    {
        $parser = $this->createGherkinParser();

        $this->expectExceptionObject(new ParserException(
            'Cannot parse file: File "inexistent-file.feature" cannot be read: file_get_contents(inexistent-file.feature): Failed to open stream: No such file or directory',
        ));

        $parser->parseFile('inexistent-file.feature');
    }

    /**
     * @return iterable<array{expectedException: Exception, featureText: string}>
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
            'expectedException' => new ParserException('Expected Step, Examples table, or end of Scenario, but got text: "    nope" in file: /fake.feature'),
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
}
