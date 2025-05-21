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
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Parser;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
        'rule.feature' => 'Rule keyword not supported',
        'rule_with_tag.feature' => 'Rule keyword not supported',
        'tags.feature' => 'Rule keyword not supported',
        'descriptions.feature' => 'Examples table descriptions not supported',
        'descriptions_with_comments.feature' => 'Examples table descriptions not supported',
        'extra_table_content.feature' => 'Table without right border triggers a ParserException',
        'feature_keyword_in_scenario_description.feature' => 'Scenario descriptions not supported',
        'padded_example.feature' => 'Table padding is not trimmed as aggressively',
        'spaces_in_language.feature' => 'Whitespace not supported around language selector',
        'incomplete_feature_3.feature' => 'file with no feature keyword not handled correctly',
        'rule_without_name_and_description.feature' => 'Rule is wrongly parsed as Description',
        'incomplete_scenario.feature' => 'Wrong background parsing when there are no steps',
        'incomplete_background_2.feature' => 'Wrong background parsing when there are no steps',
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
            $this->normaliseFeature($expected),
            $this->normaliseFeature($actual),
            Filesystem::readFile($gherkinFile)
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

    /**
     * Remove features that aren't present in the cucumber source.
     */
    private function normaliseFeature(?FeatureNode $feature): ?FeatureNode
    {
        if (is_null($feature)) {
            return null;
        }

        return new FeatureNode(
            $feature->getTitle(),
            // We currently handle whitespace in feature descriptions differently to cucumber
            // https://github.com/Behat/Gherkin/issues/209
            // We need to be able to ignore that difference so that we can still run cucumber tests that
            // include a description but are covering other features.
            $feature->getDescription() === null ? null : preg_replace('/^\s+/m', '', $feature->getDescription()),
            $feature->getTags(),
            $this->normaliseBackground($feature->getBackground()),
            array_map($this->normaliseScenario(...), $feature->getScenarios()),
            $feature->getKeyword(),
            $feature->getLanguage(),
            $feature->getFile(),
            $feature->getLine(),
        );
    }

    private function normaliseBackground(?BackgroundNode $background): ?BackgroundNode
    {
        if ($background === null) {
            return $background;
        }

        return new BackgroundNode(
            $background->getTitle(),
            array_map($this->normaliseStep(...), $background->getSteps()),
            $background->getKeyword(),
            $background->getLine(),
        );
    }

    private function normaliseScenario(ScenarioInterface $scenario): ScenarioInterface
    {
        return match ($scenario::class) {
            ScenarioNode::class => new ScenarioNode(
                $scenario->getName(),
                $scenario->getTags(),
                array_map($this->normaliseStep(...), $scenario->getSteps()),
                $scenario->getKeyword(),
                $scenario->getLine(),
            ),

            OutlineNode::class => new OutlineNode(
                $scenario->getTitle(),
                $scenario->getTags(),
                array_map($this->normaliseStep(...), $scenario->getSteps()),
                $scenario->getExampleTables(),
                $scenario->getKeyword(),
                $scenario->getLine(),
            ),

            default => throw new InvalidArgumentException('Unsupported scenario class: ' . $scenario::class),
        };
    }

    private function normaliseStep(StepNode $stepNode): StepNode
    {
        return new StepNode(
            $stepNode->getKeyword(),
            $stepNode->getText(),
            // CucumberNDJsonParser does not currently parse tables / pystrings attached to a step
            // See https://github.com/Behat/Gherkin/issues/320
            [],
            $stepNode->getLine(),
            // We cannot compare the keywordsType property on a StepNode because this concept
            // is specific to Behat/Gherkin and there is no equivalent value in the cucumber/gherkin
            // test data.
            ''
        );
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
