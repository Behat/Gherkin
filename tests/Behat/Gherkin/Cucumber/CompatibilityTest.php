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
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Parser;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

/**
 * Tests the parser against the upstream cucumber/gherkin test data.
 *
 * @group cucumber-compatibility
 */
class CompatibilityTest extends TestCase
{
    public const TESTDATA_PATH = __DIR__ . '/../../../../vendor/cucumber/cucumber/gherkin/testdata';

    private array $notParsingCorrectly = [
        'complex_background.feature' => 'Rule keyword not supported',
        'rule.feature' => 'Rule keyword not supported',
        'rule_with_tag.feature' => 'Rule keyword not supported',
        'tags.feature' => 'Rule keyword not supported',
        'descriptions.feature' => 'Examples table descriptions not supported',
        'incomplete_scenario_outline.feature' => 'Scenario and Scenario outline not yet synonyms',
        'padded_example.feature' => 'Scenario and Scenario outline not yet synonyms',
        'scenario_outline.feature' => 'Scenario and Scenario outline not yet synonyms',
        'spaces_in_language.feature' => 'Whitespace not supported around language selector',
        'incomplete_feature_3.feature' => 'file with no feature keyword not handled correctly',
        'rule_without_name_and_description.feature' => 'Rule is wrongly parsed as Description',
        'escaped_pipes.feature' => 'Feature description has wrong whitespace captured',
        'incomplete_scenario.feature' => 'Wrong background parsing when there are no steps',
        'incomplete_background_2.feature' => 'Wrong background parsing when there are no steps',
    ];

    private array $parsedButShouldNotBe = [
        'invalid_language.feature' => 'Invalid language is silently ignored',
    ];

    private array $deprecatedInsteadOfParseError = [
        'whitespace_in_tags.feature' => '/Whitespace in tags is deprecated/',
    ];

    private Parser $parser;

    private CucumberNDJsonAstLoader $loader;

    protected function setUp(): void
    {
        $arrKeywords = include __DIR__ . '/../../../../i18n.php';
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

        $actual = $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);
        $cucumberFeatures = $this->loader->load($gherkinFile . '.ast.ndjson');
        $expected = $cucumberFeatures ? $cucumberFeatures[0] : null;

        $this->assertEquals(
            $this->normaliseFeature($expected),
            $this->normaliseFeature($actual),
            file_get_contents($gherkinFile)
        );
    }

    #[DataProvider('badCucumberFeatures')]
    public function testBadFeaturesDoNotParse(SplFileInfo $file): void
    {
        if (isset($this->parsedButShouldNotBe[$file->getFilename()])) {
            $this->markTestIncomplete($this->parsedButShouldNotBe[$file->getFilename()]);
        }

        if (isset($this->deprecatedInsteadOfParseError[$file->getFilename()])) {
            $this->expectDeprecationErrorMatches($this->deprecatedInsteadOfParseError[$file->getFilename()]);
        } else {
            $this->expectException(ParserException::class);
        }
        $gherkinFile = $file->getPathname();
        $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);
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
        foreach (new FilesystemIterator(self::TESTDATA_PATH . $folder) as $file) {
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

        foreach ($featureNode->getScenarios() as $scenarioNode) {
            $steps = array_map(
                function (StepNode $step) {
                    $this->setPrivateProperty($step, 'keywordType', '');
                    $this->setPrivateProperty($step, 'arguments', []);

                    return $step;
                },
                $scenarioNode->getSteps()
            );

            $this->setPrivateProperty($scenarioNode, 'steps', $steps);
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches($message);
    }
}
