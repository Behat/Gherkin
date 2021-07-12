<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Keywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\ArrayLoader;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use Behat\Gherkin\Loader\LoaderInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Tests the parser against the upstream cucumber/gherkin test data
 *
 * @group cucumber-compatibility
 */
class CompatibilityTest extends TestCase
{
    const TESTDATA_PATH = __DIR__ . '/../../../../vendor/cucumber/cucumber/gherkin/testdata';

    private $notParsingCorrectly = [
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

    private $parsedButShouldNotBe = [
        'invalid_language.feature' => 'Invalid language is silently ignored',
    ];

    private $deprecatedInsteadOfParseError = [
        'whitespace_in_tags.feature' => '/Whitespace in tags is deprecated/',
    ];

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var LoaderInterface
     */
    private $loader;

    protected function setUp(): void
    {
        $arrKeywords = include __DIR__ . '/../../../../i18n.php';
        $lexer  = new Lexer(new Keywords\ArrayKeywords($arrKeywords));
        $this->parser = new Parser($lexer);
        $this->loader = new CucumberNDJsonAstLoader();
    }

    /**
     * @dataProvider goodCucumberFeatures
     */
    public function testFeaturesParseTheSameAsCucumber(\SplFileInfo $file)
    {

        if (isset($this->notParsingCorrectly[$file->getFilename()])){
            $this->markTestIncomplete($this->notParsingCorrectly[$file->getFilename()]);
        }

        $gherkinFile = $file->getPathname();

        $actual = $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);
        $cucumberFeatures = $this->loader->load($gherkinFile . '.ast.ndjson');
        $expected = $cucumberFeatures ? $cucumberFeatures[0] : null;

        $this->assertEquals(
            $this->normaliseFeature($expected),
            $this->normaliseFeature($actual)
        );
    }

    /**
     * @dataProvider badCucumberFeatures
     */
    public function testBadFeaturesDoNotParse(\SplFileInfo $file)
    {
        if (isset($this->parsedButShouldNotBe[$file->getFilename()])){
            $this->markTestIncomplete($this->parsedButShouldNotBe[$file->getFilename()]);
        }

        if (isset($this->deprecatedInsteadOfParseError[$file->getFilename()])) {
            $this->expectDeprecation();
            $this->expectDeprecationMessageMatches($this->deprecatedInsteadOfParseError[$file->getFilename()]);
        } else {
            $this->expectException(ParserException::class);
        }
        $gherkinFile = $file->getPathname();
        $feature = $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);
    }

    public static function goodCucumberFeatures()
    {
        return self::getCucumberFeatures('/good');
    }

    public static function badCucumberFeatures()
    {
        return self::getCucumberFeatures('/bad');
    }

    private static function getCucumberFeatures($folder)
    {
        foreach (new \FilesystemIterator(self::TESTDATA_PATH . $folder) as $file) {
            if ($file->isFile() && $file->getExtension() == 'feature') {
                yield $file->getFilename() => array($file);
            }
        }
    }

    /**
     * Renove features that aren't present in the cucumber source
     */
    private function normaliseFeature($featureNode)
    {

        if (is_null($featureNode)) {
            return null;
        }

        $scenarios = array_map(
            function(ScenarioInterface $scenarioNode) {
                $steps = array_map(
                    function(StepNode $step) {
                        $this->setPrivateProperty($step, 'keywordType', '');
                        $this->setPrivateProperty($step, 'arguments', array());

                        return $step;
                    },
                    $scenarioNode->getSteps()
                );

                $this->setPrivateProperty($scenarioNode, 'steps', $steps);

                return $scenarioNode;
            },
            $featureNode->getScenarios()
        );


        return $featureNode;
    }

    private function setPrivateProperty($object, $propertyName, $value)
    {
        $reflectionClass = new \ReflectionClass($object);
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

}
