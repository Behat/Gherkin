<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\KeywordsDumper;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

abstract class KeywordsTestCase extends TestCase
{
    abstract protected function getKeywords();

    abstract protected function getKeywordsArray();

    abstract protected function getSteps($keywords, $text, &$line, $keywordType);

    public function translationTestDataProvider()
    {
        $keywords = $this->getKeywords();
        $dumper = new KeywordsDumper($keywords);
        $keywordsArray = $this->getKeywordsArray();

        // Remove languages with repeated keywords
        unset($keywordsArray['en-old'], $keywordsArray['uz'], $keywordsArray['ne']);

        $data = [];
        foreach ($keywordsArray as $lang => $i18nKeywords) {
            $features = [];
            foreach (explode('|', $i18nKeywords['feature']) as $transNum => $featureKeyword) {
                $line = 1;
                if ($lang !== 'en') {
                    $line = 2;
                }

                $featureLine = $line;
                $line += 5;

                $keywords = explode('|', $i18nKeywords['background']);
                $backgroundLine = $line;
                ++$line;
                $background = new BackgroundNode(null, array_merge(
                    $this->getSteps($i18nKeywords['given'], 'there is agent A', $line, 'Given'),
                    $this->getSteps($i18nKeywords['and'], 'there is agent B', $line, 'Given')
                ), $keywords[0], $backgroundLine);

                ++$line;

                $scenarios = [];

                foreach (explode('|', $i18nKeywords['scenario']) as $scenarioKeyword) {
                    $scenarioLine = $line;
                    ++$line;

                    $steps = array_merge(
                        $this->getSteps($i18nKeywords['given'], 'there is agent J', $line, 'Given'),
                        $this->getSteps($i18nKeywords['and'], 'there is agent K', $line, 'Given'),
                        $this->getSteps($i18nKeywords['when'], 'I erase agent K\'s memory', $line, 'When'),
                        $this->getSteps($i18nKeywords['then'], 'there should be agent J', $line, 'Then'),
                        $this->getSteps($i18nKeywords['but'], 'there should not be agent K', $line, 'Then')
                    );

                    $scenarios[] = new ScenarioNode('Erasing agent memory', [], $steps, $scenarioKeyword, $scenarioLine);
                    ++$line;
                }
                foreach (explode('|', $i18nKeywords['scenario_outline']) as $outlineKeyword) {
                    $outlineLine = $line;
                    ++$line;

                    $steps = array_merge(
                        $this->getSteps($i18nKeywords['given'], 'there is agent <agent1>', $line, 'Given'),
                        $this->getSteps($i18nKeywords['and'], 'there is agent <agent2>', $line, 'Given'),
                        $this->getSteps($i18nKeywords['when'], 'I erase agent <agent2>\'s memory', $line, 'When'),
                        $this->getSteps($i18nKeywords['then'], 'there should be agent <agent1>', $line, 'Then'),
                        $this->getSteps($i18nKeywords['but'], 'there should not be agent <agent2>', $line, 'Then')
                    );
                    ++$line;

                    $keywords = explode('|', $i18nKeywords['examples']);
                    $table = new ExampleTableNode([
                        ++$line => ['agent1', 'agent2'],
                        ++$line => ['D', 'M'],
                    ], $keywords[0]);
                    ++$line;

                    $scenarios[] = new OutlineNode('Erasing other agents\' memory', [], $steps, $table, $outlineKeyword, $outlineLine);
                    ++$line;
                }

                $features[] = new FeatureNode(
                    'Internal operations',
                    <<<DESC
In order to stay secret
As a secret organization
We need to be able to erase past agents' memory
DESC
                    ,
                    [],
                    $background,
                    $scenarios,
                    $featureKeyword,
                    $lang,
                    __DIR__ . DIRECTORY_SEPARATOR . $lang . '_' . ($transNum + 1) . '.feature',
                    $featureLine
                );
            }

            $dumped = $dumper->dump($lang, false, true);

            foreach ($dumped as $num => $dumpedFeature) {
                $data[$lang . '_' . $num] = [$lang, $num, $features[$num], $dumpedFeature];
            }
        }

        return $data;
    }

    /**
     * @dataProvider translationTestDataProvider
     *
     * @param string $language language name
     * @param int $num Fixture index for that language
     * @param FeatureNode $etalon etalon features (to test against)
     * @param string $source gherkin source
     */
    public function testTranslation($language, $num, FeatureNode $etalon, $source)
    {
        $keywords = $this->getKeywords();
        $lexer = new Lexer($keywords);
        $parser = new Parser($lexer);

        try {
            $parsed = $parser->parse($source, __DIR__ . DIRECTORY_SEPARATOR . $language . '_' . ($num + 1) . '.feature');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage() . ":\n" . $source, 0, $e);
        }

        $this->assertEquals($etalon, $parsed);
    }
}
