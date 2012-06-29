<?php

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Node,
    Behat\Gherkin\Keywords\KeywordsDumper,
    Behat\Gherkin\Keywords\CucumberKeywords;

abstract class KeywordsTest extends \PHPUnit_Framework_TestCase
{
    public function translationTestDataProvider()
    {
        $keywords = $this->getKeywords();
        $lexer    = new Lexer($keywords);
        $parser   = new Parser($lexer);
        $dumper   = new KeywordsDumper($keywords);

        $data = array();
        foreach ($this->getKeywordsArray() as $lang => $i18nKeywords) {
            $features = array();
            foreach (explode('|', $i18nKeywords['feature']) as $transNum => $featureKeyword) {
                $line = 1;
                if ('en' !== $lang) {
                    $line = 2;
                }

                $feature = new Node\FeatureNode(
                    'Internal operations',
                    <<<DESC
In order to stay secret
As a secret organization
We need to be able to erase past agents' memory
DESC
                    , $lang.'_'.($transNum+1).'.feature',
                    $line
                );
                $feature->setLanguage($lang);
                $feature->setKeyword($featureKeyword);
                $line += 5;

                $background = new Node\BackgroundNode(null, $line);
                $keywords = explode('|', $i18nKeywords['background']);
                $background->setKeyword($keywords[0]);
                $line += 1;

                $line = $this->addSteps(
                    $background, $i18nKeywords['given'], 'there is agent A', $line
                );
                $line = $this->addSteps(
                    $background, $i18nKeywords['and'], 'there is agent B', $line
                );
                $feature->setBackground($background);
                $line += 1;

                foreach (explode('|', $i18nKeywords['scenario']) as $scenarioKeyword) {
                    $scenario = new Node\ScenarioNode('Erasing agent memory', $line);
                    $scenario->setKeyword($scenarioKeyword);
                    $line += 1;

                    $line = $this->addSteps(
                        $scenario, $i18nKeywords['given'], 'there is agent J', $line
                    );
                    $line = $this->addSteps(
                        $scenario, $i18nKeywords['and'], 'there is agent K', $line
                    );
                    $line = $this->addSteps(
                        $scenario, $i18nKeywords['when'], 'I erase agent K\'s memory', $line
                    );
                    $line = $this->addSteps(
                        $scenario, $i18nKeywords['then'], 'there should be agent J', $line
                    );
                    $line = $this->addSteps(
                        $scenario, $i18nKeywords['but'], 'there should not be agent K', $line
                    );
                    $feature->addScenario($scenario);
                    $line += 1;
                }

                foreach (explode('|', $i18nKeywords['scenario_outline']) as $outlineKeyword) {
                    $outline = new Node\OutlineNode('Erasing other agents\' memory', $line);
                    $outline->setKeyword($outlineKeyword);
                    $line += 1;

                    $line = $this->addSteps(
                        $outline, $i18nKeywords['given'], 'there is agent <agent1>', $line
                    );
                    $line = $this->addSteps(
                        $outline, $i18nKeywords['and'], 'there is agent <agent2>', $line
                    );
                    $line = $this->addSteps(
                        $outline, $i18nKeywords['when'], 'I erase agent <agent2>\'s memory', $line
                    );
                    $line = $this->addSteps(
                        $outline, $i18nKeywords['then'], 'there should be agent <agent1>', $line
                    );
                    $line = $this->addSteps(
                        $outline, $i18nKeywords['but'], 'there should not be agent <agent2>', $line
                    );
                    $line += 1;

                    $examples = new Node\TableNode();
                    $examples->addRow('                      | agent1 | agent2 |', ++$line);
                    $examples->addRow('                      | D      | M      |', ++$line);
                    $outline->setExamples($examples);
                    $keywords = explode('|', $i18nKeywords['examples']);
                    $examples->setKeyword($keywords[0]);
                    $line += 1;

                    $feature->addScenario($outline);
                    $line += 1;
                }

                $features[] = $feature;
            }

            $dumped = $dumper->dump($lang, false);
            $parsed = array();
            try {
                foreach ($dumped as $num => $dumpedFeature) {
                    $parsed[] = $parser->parse($dumpedFeature, $lang.'_'.($num+1).'.feature');
                }
            } catch (\Exception $e) {
                throw new \Exception(
                    $e->getMessage().":\n".$dumped, 0, $e
                );
            }

            $data[] = array($lang, $features, $parsed);
        }

        return $data;
    }

    /**
     * @dataProvider translationTestDataProvider
     *
     * @param   string  $language   language name
     * @param   array   $etalon     etalon features (to test against)
     * @param   array   $features   array of parsed feature(s)
     */
    public function testTranslation($language, array $etalon, array $features)
    {
        $this->assertEquals($etalon, $features);
    }
}
