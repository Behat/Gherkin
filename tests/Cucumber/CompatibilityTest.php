<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Keywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use Behat\Gherkin\Parser;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\Comparator\Factory;
use SplFileInfo;
use Tests\Behat\Gherkin\Filesystem;

/**
 * Tests the parser against the upstream cucumber/gherkin test data.
 *
 * @group cucumber-compatibility
 */
class CompatibilityTest extends TestCase
{
    private const GHERKIN_TESTDATA_PATH = __DIR__ . '/../../vendor/cucumber/gherkin-monorepo/testdata';
    private const EXTRA_TESTDATA_PATH = __DIR__ . '/extra_testdata';

    /**
     * @var array<string, string>
     */
    private array $notParsingCorrectly = [
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
    ];

    /**
     * @var array<string, string>
     */
    private array $parsedButShouldNotBe = [
        'invalid_language.feature' => 'Invalid language is silently ignored',
    ];

    /**
     * @var array<string, string>
     */
    private array $deprecatedInsteadOfParseError = [
        'whitespace_in_tags.feature' => '/Whitespace in tags is deprecated/',
    ];

    private Parser $parser;

    private CucumberNDJsonAstLoader $loader;

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
        $arrKeywords = include __DIR__ . '/../../i18n.php';
        $lexer = new Lexer(new Keywords\ArrayKeywords($arrKeywords));
        $this->parser = new Parser($lexer);
        $this->loader = new CucumberNDJsonAstLoader();
    }

    #[DataProvider('goodCucumberFeatures')]
    public function testFeaturesParseTheSameAsCucumber(SplFileInfo $file): void
    {
        if (isset($this->notParsingCorrectly[$file->getFilename()])) {
            $this->markTestIncomplete($this->notParsingCorrectly[$file->getFilename()]);
        }

        $gherkinFile = $file->getPathname();
        $actual = $this->parser->parse(Filesystem::readFile($gherkinFile), $gherkinFile);
        $cucumberFeatures = $this->loader->load($gherkinFile . '.ast.ndjson');

        $expected = $cucumberFeatures ? $cucumberFeatures[0] : null;

        $this->assertEquals(
            $expected,
            $actual,
            Filesystem::readFile($gherkinFile),
        );
    }

    #[DataProvider('badCucumberFeatures')]
    public function testBadFeaturesDoNotParse(SplFileInfo $file): void
    {
        if (isset($this->parsedButShouldNotBe[$file->getFilename()])) {
            $this->markTestIncomplete($this->parsedButShouldNotBe[$file->getFilename()]);
        }

        $gherkinFile = $file->getPathname();

        if (isset($this->deprecatedInsteadOfParseError[$file->getFilename()])) {
            $this->expectDeprecationErrorMatches($this->deprecatedInsteadOfParseError[$file->getFilename()]);
        } else {
            // Note that the exception message is not part of compatibility testing and therefore cannot be checked.
            $this->expectException(ParserException::class);
        }

        $this->parser->parse(Filesystem::readFile($gherkinFile), $gherkinFile);
    }

    /**
     * @return iterable<string, array{file: SplFileInfo}>
     */
    public static function goodCucumberFeatures(): iterable
    {
        yield from self::getCucumberFeatures(self::GHERKIN_TESTDATA_PATH . '/good');
        yield from self::getCucumberFeatures(self::EXTRA_TESTDATA_PATH . '/good');
    }

    /**
     * @return iterable<string, array{file: SplFileInfo}>
     */
    public static function badCucumberFeatures(): iterable
    {
        yield from self::getCucumberFeatures(self::GHERKIN_TESTDATA_PATH . '/bad');
        yield from self::getCucumberFeatures(self::EXTRA_TESTDATA_PATH . '/bad');
    }

    /**
     * @return iterable<string, array{file: SplFileInfo}>
     */
    private static function getCucumberFeatures(string $folder): iterable
    {
        $fileIterator = new FilesystemIterator($folder);
        /**
         * @var iterable<string, SplFileInfo> $fileIterator
         */
        foreach ($fileIterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'feature') {
                yield $file->getFilename() => ['file' => $file];
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
