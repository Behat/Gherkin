<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Dialect\CucumberDialectProvider;
use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Filesystem;
use Behat\Gherkin\GherkinCompatibilityMode;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Comparator\Factory;
use SplFileInfo;

/**
 * Tests the parser against the upstream cucumber/gherkin test data.
 *
 * @group cucumber-compatibility
 *
 * @phpstan-type TCucumberParsingTestCase array{mode: GherkinCompatibilityMode, file: SplFileInfo}
 * @phpstan-type TKnownIncompatibilityMap array<value-of<GherkinCompatibilityMode>, array<string,string>>
 */
class CompatibilityTest extends TestCase
{
    private const GHERKIN_TESTDATA_PATH = __DIR__ . '/../../vendor/cucumber/gherkin-monorepo/testdata';
    private const EXTRA_TESTDATA_PATH = __DIR__ . '/extra_testdata';

    /**
     * @phpstan-var TKnownIncompatibilityMap
     */
    private array $notParsingCorrectly = [
        'legacy' => [
            'complex_background.feature' => 'Rule keyword not supported',
            'docstrings.feature' => 'Escaped delimiters in docstrings are not unescaped',
            'datatables_with_new_lines.feature' => 'Escaped newlines in table cells are not unescaped',
            'escaped_pipes.feature' => 'Escaped newlines in table cells are not unescaped',
            'rule.feature' => 'Rule keyword not supported',
            'rule_with_tag.feature' => 'Rule keyword not supported',
            'tags.feature' => 'Rule keyword not supported',
            'descriptions.feature' => 'Examples table descriptions not supported',
            'descriptions_with_comments.feature' => 'Examples table descriptions not supported',
            'feature_keyword_in_scenario_description.feature' => 'Scenario descriptions not supported',
            'padded_example.feature' => 'Table padding is not trimmed as aggressively',
            'spaces_in_language.feature' => 'Whitespace not supported around language selector',
            'rule_without_name_and_description.feature' => 'Rule is wrongly parsed as Description',
            'incomplete_background_2.feature' => 'Background descriptions not supported',
            'examples_keyword_in_background_description.feature' => 'Background descriptions not supported',
        ],
        'gherkin-32' => [
            'complex_background.feature' => 'Rule keyword not supported',
            'docstrings.feature' => 'Escaped delimiters in docstrings are not unescaped',
            'escaped_pipes.feature' => 'Escaped newlines in table cells are not unescaped',
            'rule.feature' => 'Rule keyword not supported',
            'rule_with_tag.feature' => 'Rule keyword not supported',
            'tags.feature' => 'Rule keyword not supported',
            'padded_example.feature' => 'Table padding is not trimmed as aggressively',
            'rule_without_name_and_description.feature' => 'Rule is wrongly parsed as Description',
        ],
    ];

    /**
     * @phpstan-var TKnownIncompatibilityMap
     */
    private array $parsedButShouldNotBe = [
        'legacy' => [
            'invalid_language.feature' => 'Invalid language is silently ignored',
        ],
        'gherkin-32' => [
        ],
    ];

    /**
     * @phpstan-var TKnownIncompatibilityMap
     */
    private array $deprecatedInsteadOfParseError = [
        'legacy' => [
            'whitespace_in_tags.feature' => '/Whitespace in tags is deprecated/',
        ],
        'gherkin-32' => [
            'whitespace_in_tags.feature' => '/Whitespace in tags is deprecated/',
        ],
    ];

    private Parser $parser;

    private NDJsonAstParser $ndJsonAstParser;

    private static ?StepNodeComparator $stepNodeComparator = null;

    private static ?FeatureNodeComparator $featureNodeComparator = null;

    public static function setUpBeforeClass(): void
    {
        self::$stepNodeComparator = new StepNodeComparator();
        Factory::getInstance()->register(self::$stepNodeComparator);
        self::$featureNodeComparator = new FeatureNodeComparator();
        Factory::getInstance()->register(self::$featureNodeComparator);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$stepNodeComparator !== null) {
            Factory::getInstance()->unregister(self::$stepNodeComparator);
            self::$stepNodeComparator = null;
        }
        if (self::$featureNodeComparator !== null) {
            Factory::getInstance()->unregister(self::$featureNodeComparator);
            self::$featureNodeComparator = null;
        }
    }

    protected function setUp(): void
    {
        $lexer = new Lexer(new CucumberDialectProvider());
        $this->parser = new Parser($lexer);
        $this->ndJsonAstParser = new NDJsonAstParser();
    }

    #[DataProvider('goodCucumberFeatures')]
    public function testFeaturesParseTheSameAsCucumber(GherkinCompatibilityMode $mode, SplFileInfo $file): void
    {
        if (isset($this->notParsingCorrectly[$mode->value][$file->getFilename()])) {
            $this->markTestIncomplete($this->notParsingCorrectly[$mode->value][$file->getFilename()]);
        }

        assert(self::$featureNodeComparator instanceof FeatureNodeComparator);
        assert(self::$stepNodeComparator instanceof StepNodeComparator);
        self::$featureNodeComparator->setGherkinCompatibilityMode($mode);
        self::$stepNodeComparator->setGherkinCompatibilityMode($mode);
        $this->parser->setGherkinCompatibilityMode($mode);

        $gherkinFile = $file->getPathname();
        $actual = $this->parser->parseFile($gherkinFile);
        $cucumberFeatures = $this->ndJsonAstParser->load($gherkinFile . '.ast.ndjson');

        $expected = $cucumberFeatures ? $cucumberFeatures[0] : null;

        $this->assertEquals(
            $expected,
            $actual,
            Filesystem::readFile($gherkinFile),
        );
    }

    #[DataProvider('badCucumberFeatures')]
    public function testBadFeaturesDoNotParse(GherkinCompatibilityMode $mode, SplFileInfo $file): void
    {
        if (isset($this->parsedButShouldNotBe[$mode->value][$file->getFilename()])) {
            $this->markTestIncomplete($this->parsedButShouldNotBe[$mode->value][$file->getFilename()]);
        }

        $gherkinFile = $file->getPathname();
        $this->parser->setGherkinCompatibilityMode($mode);

        if (isset($this->deprecatedInsteadOfParseError[$mode->value][$file->getFilename()])) {
            $this->expectDeprecationErrorMatches(
                $this->deprecatedInsteadOfParseError[$mode->value][$file->getFilename()],
            );
        } else {
            // Note that the exception message is not part of compatibility testing and therefore cannot be checked.
            $this->expectException(ParserException::class);
        }

        $this->parser->parseFile($gherkinFile);
    }

    /**
     * @phpstan-return iterable<string, TCucumberParsingTestCase>
     */
    public static function goodCucumberFeatures(): iterable
    {
        yield from self::getCucumberFeatures(self::GHERKIN_TESTDATA_PATH . '/good');
        yield from self::getCucumberFeatures(self::EXTRA_TESTDATA_PATH . '/good');
    }

    /**
     * @phpstan-return iterable<string, TCucumberParsingTestCase>
     */
    public static function badCucumberFeatures(): iterable
    {
        yield from self::getCucumberFeatures(self::GHERKIN_TESTDATA_PATH . '/bad');
        yield from self::getCucumberFeatures(self::EXTRA_TESTDATA_PATH . '/bad');
    }

    /**
     * @phpstan-return iterable<string, TCucumberParsingTestCase>
     */
    private static function getCucumberFeatures(string $folder): iterable
    {
        $fileIterator = new FilesystemIterator($folder);
        /**
         * @var iterable<string, SplFileInfo> $fileIterator
         */
        foreach ($fileIterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'feature') {
                foreach (GherkinCompatibilityMode::cases() as $mode) {
                    yield $file->getFilename() . ' (' . $mode->value . ')' => [
                        'mode' => $mode,
                        'file' => $file,
                    ];
                }
            }
        }
    }

    private function expectDeprecationErrorMatches(string $message): void
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                throw new RuntimeException($errstr, $errno);
            },
            E_ALL
        );

        $this->expectExceptionMessageMatches($message);
        $this->expectException(RuntimeException::class);
    }
}
