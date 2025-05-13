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
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
    public const TESTDATA_PATH = __DIR__ . '/../../vendor/cucumber/gherkin-monorepo/testdata';

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
        'incomplete_scenario_outline.feature' => 'Scenario and Scenario outline not yet synonyms',
        'padded_example.feature' => 'Scenario and Scenario outline not yet synonyms',
        'scenario_outline.feature' => 'Scenario and Scenario outline not yet synonyms',
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
        return self::getCucumberFeatures('/good');
    }

    /**
     * @return iterable<string, array{file: SplFileInfo}>
     */
    public static function badCucumberFeatures(): iterable
    {
        return self::getCucumberFeatures('/bad');
    }

    /**
     * @return iterable<string, array{file: SplFileInfo}>
     */
    private static function getCucumberFeatures(string $folder): iterable
    {
        $fileIterator = new FilesystemIterator(self::TESTDATA_PATH . $folder);
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
    private function normaliseFeature(?FeatureNode $featureNode): ?FeatureNode
    {
        if (is_null($featureNode)) {
            return null;
        }

        if ($featureNode->getDescription() !== null) {
            // We currently handle whitespace in feature descriptions differently to cucumber
            // https://github.com/Behat/Gherkin/issues/209
            // We need to be able to ignore that difference so that we can still run cucumber tests that
            // include a description but are covering other features.
            $trimmedDescription = preg_replace('/^\s+/m', '', $featureNode->getDescription());
            $this->setPrivateProperty($featureNode, 'description', $trimmedDescription);
        }

        foreach ($featureNode->getScenarios() as $scenarioNode) {
            foreach ($scenarioNode->getSteps() as $step) {
                $this->setPrivateProperty($step, 'keywordType', '');
                $this->setPrivateProperty($step, 'arguments', []);
            }
        }

        return $featureNode;
    }

    private function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflectionClass = new ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setValue($object, $value);
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
