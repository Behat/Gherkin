<?php

namespace Tests\Behat\Gherkin\Keywords;

use Symfony\Component\Finder\Finder,
    Symfony\Component\Translation\Translator,
    Symfony\Component\Translation\MessageSelector,
    Symfony\Component\Translation\Loader\XliffFileLoader;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Node,
    Behat\Gherkin\Keywords\SymfonyTranslationKeywords;

class SymfonyTranslationKeywordsTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $parser;

    public function translationTestDataProvider()
    {
        $data = array();

        $translator = $this->getTranslator();
        $parser     = $this->getParser();

        $finder     = new Finder();
        $i18ns      = $finder->files()->name('*.xliff')->in(__DIR__ . '/../Fixtures/i18n');

        foreach ($i18ns as $i18n) {
            $language = basename($i18n, '.xliff');
            $translator->addResource('xliff', $i18n, $language, 'gherkin');

            $etalon   = array();
            $features = array();
            foreach ($this->getTranslatedKeywords('Feature', $language) as $featureNum => $featureKeyword) {
                $gherkin = "# language: $language";
                $lineNum = 1;

                $feature = new Node\FeatureNode(null, null, null, ++$lineNum);
                $feature->setLanguage($language);
                $feature->setKeyword($featureKeyword);
                $feature->setTitle($title = "title of the feature N$featureNum");
                $feature->setDescription($description = "some\nfeature\ndescription");

                $gherkin .= "\n$featureKeyword: $title";
                $gherkin .= "\n$description";
                $lineNum += 3;

                $stepKeywords       = $this->getTranslatedKeywords('Given|When|Then|And|But', $language);
                $backgroundKeywords = $this->getTranslatedKeywords('Background', $language);
                $examplesKeywords   = $this->getTranslatedKeywords('Examples', $language);

                // Background
                $backgroundKeyword = $backgroundKeywords[0];
                $background = new Node\BackgroundNode(null, ++$lineNum);
                $background->setKeyword($backgroundKeyword);
                $feature->setBackground($background);

                $gherkin .= "\n$backgroundKeyword:";

                foreach ($stepKeywords as $stepNum => $stepKeyword) {
                    $step = new Node\StepNode($stepKeyword, $text = "text of the step N$stepNum", ++$lineNum);
                    $background->addStep($step);

                    $gherkin .= "\n$stepKeyword $text";
                }

                // Scenarios
                foreach ($this->getTranslatedKeywords('Scenario', $language) as $scenarioNum => $scenarioKeyword) {
                    $scenario = new Node\ScenarioNode($title = "title of the scenario N$scenarioNum", ++$lineNum);
                    $scenario->setKeyword($scenarioKeyword);
                    $feature->addScenario($scenario);

                    $gherkin .= "\n$scenarioKeyword: $title";

                    foreach ($stepKeywords as $stepNum => $stepKeyword) {
                        $step = new Node\StepNode($stepKeyword, $text = "text of the step N$stepNum", ++$lineNum);
                        $scenario->addStep($step);

                        $gherkin .= "\n$stepKeyword $text";
                    }
                }

                // Scenario Outlines
                foreach ($this->getTranslatedKeywords('Scenario Outline', $language) as $outlineNum => $outlineKeyword) {
                    $outline = new Node\OutlineNode($title = "title of the outline N$outlineNum", ++$lineNum);
                    $outline->setKeyword($outlineKeyword);
                    $feature->addScenario($outline);

                    $gherkin .= "\n$outlineKeyword: $title";

                    $stepKeyword = $stepKeywords[0];
                    $step = new Node\StepNode($stepKeyword, $text = "text of the step <num>", ++$lineNum);
                    $outline->addStep($step);

                    $gherkin .= "\n$stepKeyword $text";

                    $examplesKeyword = $examplesKeywords[0];
                    $examples = new Node\TableNode();
                    $examples->setKeyword($examplesKeyword);
                    $lineNum += 1;

                    $examples->addRow(array('num'), ++$lineNum);
                    $examples->addRow(array(2), ++$lineNum);
                    $outline->setExamples($examples);

                    $gherkin .= "\n$examplesKeyword:";
                    $gherkin .= "\n  | num |";
                    $gherkin .= "\n  | 2   |";
                }

                $etalon[]   = $feature;
                $features[] = $this->getParser()->parse($gherkin);
            }

            $data[] = array($language, $etalon, $features);
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

    protected function getParser()
    {
        if (null === $this->parser) {
            $keywords       = new SymfonyTranslationKeywords($this->getTranslator());
            $lexer          = new Lexer($keywords);
            $this->parser   = new Parser($lexer);
        }

        return $this->parser;
    }

    protected function getTranslator()
    {
        if (null === $this->translator) {
            $this->translator = new Translator(null, new MessageSelector());
            $this->translator->addLoader('xliff', new XliffFileLoader());
        }

        return $this->translator;
    }

    protected function getTranslatedKeywords($keyword, $language)
    {
        return explode('|', $this->translate($keyword, $language));
    }

    protected function translate($keyword, $language)
    {
        return $this->getTranslator()->trans($keyword, array(), 'gherkin', $language);
    }
}
