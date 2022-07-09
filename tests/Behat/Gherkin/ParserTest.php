<?php

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Loader\YamlFileLoader;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private $gherkin;
    private $yaml;

    public function testParserResetsTagsBetweenFeatures()
    {
        $parser = $this->getGherkinParser();

        $parser->parse(<<<FEATURE
Feature:
Scenario:
Given step
@skipped
FEATURE
        );
        $feature2 = $parser->parse(<<<FEATURE
Feature:
Scenario:
Given step
FEATURE
        );

        $this->assertFalse($feature2->hasTags());
    }

    public function testSingleCharacterStepSupport()
    {
        $feature = $this->getGherkinParser()->parse(<<<FEATURE
Feature:
Scenario:
When x
FEATURE
);

        $scenarios = $feature->getScenarios();
        /** @var ScenarioNode $scenario */
        $scenario = array_shift($scenarios);

        $this->assertCount(1, $scenario->getSteps());
    }

    protected function getGherkinParser()
    {
        if (null === $this->gherkin) {
            $keywords       = new ArrayKeywords(array(
                'en' => array(
                    'feature'          => 'Feature',
                    'background'       => 'Background',
                    'scenario'         => 'Scenario',
                    'scenario_outline' => 'Scenario Outline',
                    'examples'         => 'Examples',
                    'given'            => 'Given',
                    'when'             => 'When',
                    'then'             => 'Then',
                    'and'              => 'And',
                    'but'              => 'But'
                )
            ));
            $this->gherkin  = new Parser(new Lexer($keywords));
        }

        return $this->gherkin;
    }

    public function testParsingManyCommentsShouldPass()
    {
        if (! extension_loaded('xdebug')) {
            $this->markTestSkipped('xdebug extension must be enabled.');
        }
        $defaultPHPSetting = 256;
        $this->iniSet('xdebug.max_nesting_level', $defaultPHPSetting);

        $lineCount = 150; // 119 is the real threshold, higher just in case
        $this->assertNull($this->getGherkinParser()->parse(str_repeat("# \n", $lineCount)));
    }
}
