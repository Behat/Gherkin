<?php

namespace Behat\Gherkin\Cucumber;

use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Keywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Loader\ArrayLoader;
use Behat\Gherkin\Loader\CucumberNDJsonAstLoader;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

/**
 * @group cucumber-compatibility
 */
class CompatibilityTest extends TestCase
{
    const TESTDATA_PATH = __DIR__ . '/../../../../vendor/cucumber/cucumber/gherkin/testdata';

    const NOT_PARSABLE = [
        'complex_background.feature',
        'descriptions.feature',
        'docstrings.feature',
        'incomplete.feature',
        'incomplete_scenario_outline.feature',
        'padded_example.feature',
        'rule.feature',
        'scenario_outline.feature',
        'spaces_in_language.feature',
        'empty.feature',
        'incomplete_feature_3.feature'
    ];

    /**
     * @var Parser
     */
    private $parser;

    public function setUp()
    {
        $arrKeywords = include __DIR__ . '/../../../../i18n.php';
        $lexer  = new Lexer(new Keywords\ArrayKeywords($arrKeywords));
        $this->parser = new Parser($lexer);
    }

    /**
     * @dataProvider goodFeatures
     */
    public function testGoodFeaturesCanParse($gherkinFile)
    {
        $actual = $this->parser->parse(file_get_contents($gherkinFile), $gherkinFile);

        $this->assertInstanceOf(FeatureNode::class, $actual);
    }

    public static function goodFeatures()
    {
        foreach (new \FilesystemIterator(self::TESTDATA_PATH . '/good') as $file) {
            if (in_array($file->getFilename(), self::NOT_PARSABLE)) {
                continue;
            }

            if ($file->isFile() && $file->getExtension() === 'feature') {
                yield $file->getFilename() => [ $file->getPathname(), $file->getPathname() . '.ast.ndjson' ];
            }
        }
    }
}