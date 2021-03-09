<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Keywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\ArrayLoader;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use Behat\Gherkin\Loader\LoaderInterface;
use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Behat and Cucumber parsers against each other
 *
 * @group cucumber-compatibility
 */
class CompatibilityTest extends TestCase
{
    const CUCUMBER_TEST_DATA = __DIR__ . '/../../../../vendor/cucumber/cucumber/gherkin/testdata';
    const BEHAT_TEST_DATA = __DIR__ . '/../Fixtures/etalons';

    private $cucumberFeaturesNotParsingCorrectly = [
        'complex_background.feature' => 'Rule keyword not supported',
        'rule.feature' => 'Rule keyword not supported',
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
        'tags.feature' => 'Tags followed by comments not parsed correctly'
    ];

    private $behatFeaturesNotParsingCorrectly = [
        'issue_13.yml' => 'Scenario descriptions are not supported',
        'complex_descriptions.yml' => 'Scenario descriptions are not supported',
        'multiline_name_with_newlines.yml' => 'Scenario descriptions are not supported',
        'multiline_name.yml' => 'Scenario descriptions are not supported',
        'background_title.yml' => 'Background descriptions are not supported',

        'empty_scenario_without_linefeed.yml' => 'Feature description has wrong whitespace captured',
        'addition.yml' => 'Feature description has wrong whitespace captured',
        'test_unit.yml' => 'Feature description has wrong whitespace captured',
        'ja_addition.yml' => 'Feature description has wrong whitespace captured',
        'ru_addition.yml' => 'Feature description has wrong whitespace captured',
        'fibonacci.yml' => 'Feature description has wrong whitespace captured',
        'ru_commented.yml' => 'Feature description has wrong whitespace captured',
        'empty_scenario.yml' => 'Feature description has wrong whitespace captured',
        'start_comments.yml' => 'Feature description has wrong whitespace captured',
        'empty_scenarios.yml' => 'Feature description has wrong whitespace captured',
        'commented_out.yml' => 'Feature description has wrong whitespace captured',
        'ru_division.yml' => 'Feature description has wrong whitespace captured',
        'hashes_in_quotes.yml' => 'Feature description has wrong whitespace captured',
        'outline_with_spaces.yml' => 'Feature description has wrong whitespace captured',
        'ru_consecutive_calculations.yml' => 'Feature description has wrong whitespace captured',
    ];

    private $behatFeaturesCucumberCannotParseCorrectly = [
        'comments.yml' => 'see https://github.com/cucumber/cucumber/issues/1413'
    ];

    private $cucumberFeaturesParsedButShouldNotBe = [
        'invalid_language.feature' => 'Invalid language is silently ignored',
        'whitespace_in_tags.feature' => 'Whitespace in tags is tolerated',
    ];

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var LoaderInterface
     */
    private $cucumberLoader;

    /**
     * @var LoaderInterface
     */
    private $yamlLoader;

    protected function setUp(): void
    {
        $arrKeywords = include __DIR__ . '/../../../../i18n.php';
        $lexer  = new Lexer(new Keywords\ArrayKeywords($arrKeywords));
        $this->parser = new Parser($lexer);
        $this->cucumberLoader = new CucumberNDJsonAstLoader();
        $this->yamlLoader = new YamlFileLoader();
    }

    /**
     * @dataProvider goodCucumberFeatures
     */
    public function testCucumberFeaturesParseTheSame(\SplFileInfo $file)
    {
        if (isset($this->cucumberFeaturesNotParsingCorrectly[$file->getFilename()])){
            $this->markTestIncomplete($this->cucumberFeaturesNotParsingCorrectly[$file->getFilename()]);
        }

        $gherkinFile = $file->getPathname();

        $actual = $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);
        $cucumberFeatures = $this->cucumberLoader->load($gherkinFile . '.ast.ndjson');
        $expected = $cucumberFeatures ? $cucumberFeatures[0] : null;

        $this->assertEquals(
            $this->normaliseFeature($expected),
            $this->normaliseFeature($actual)
        );
    }

    /**
     * @dataProvider behatFeatures
     */
    public function testBehatFeaturesParseTheSame(\SplFileInfo $ymlFile)
    {
        if (isset($this->behatFeaturesNotParsingCorrectly[$ymlFile->getFilename()])){
            $this->markTestIncomplete($this->behatFeaturesNotParsingCorrectly[$ymlFile->getFilename()]);
        }

        if (isset($this->behatFeaturesCucumberCannotParseCorrectly[$ymlFile->getFilename()])){
            $this->markTestIncomplete($this->behatFeaturesCucumberCannotParseCorrectly[$ymlFile->getFilename()]);
            return;
        }

        exec('which gherkin', $_, $result);
        if ($result) {
            $this->markTestSkipped("No gherkin executable in path");
        }

        $filename = $ymlFile->getPathname();
        $expected = current($this->yamlLoader->load($filename));

        $featureFile = preg_replace('/etalons\/(.*).yml$/', 'features/\\1.feature', $filename);

        $tempFile = tempnam(sys_get_temp_dir(), 'behat-cucumber');
        exec("gherkin -format ndjson -no-source -no-pickles $featureFile > $tempFile");
        $actual = current($this->cucumberLoader->load($tempFile));
        unlink($tempFile);

        $this->assertEquals($this->normaliseFeature($expected), $this->normaliseFeature($actual));
    }

    /**
     * @dataProvider badCucumberFeatures
     */
    public function testBadCucumberFeaturesDoNotParse(\SplFileInfo $file)
    {
        if (isset($this->cucumberFeaturesParsedButShouldNotBe[$file->getFilename()])){
            $this->markTestIncomplete($this->cucumberFeaturesParsedButShouldNotBe[$file->getFilename()]);
        }

        $this->expectException(ParserException::class);
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
        foreach (new \FilesystemIterator(self::CUCUMBER_TEST_DATA . $folder) as $file) {
            if ($file->isFile() && $file->getExtension() == 'feature') {
                yield $file->getFilename() => array($file);
            }
        }
    }

    public static function behatFeatures(): iterable
    {
        foreach (new \FilesystemIterator(self::BEHAT_TEST_DATA) as $file) {
            if ($file->isFile() && $file->getExtension() == 'yml') {
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

        array_map(
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

        $this->setPrivateProperty($featureNode, 'file', 'file.feature');

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
