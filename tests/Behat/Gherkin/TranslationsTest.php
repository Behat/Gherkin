<?php

namespace Tests\Behat\Gherkin;

require_once 'Fixtures/YamlParser.php';

use Symfony\Component\Finder\Finder,
    Symfony\Component\Translation\Translator,
    Symfony\Component\Translation\MessageSelector;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Node,
    Behat\Gherkin\Keywords\SymfonyTranslationKeywords;

use Tests\Behat\Gherkin\Fixtures\YamlParser;

class TranslationsTest extends \PHPUnit_Framework_TestCase
{
    private $translator;
    private $parser;

    protected function setUp()
    {
        $this->translator = new Translator('en', new MessageSelector());

        $keywords = new SymfonyTranslationKeywords($this->translator);
        $keywords->setXliffTranslationsPath(__DIR__ . '/../../../i18n');

        $this->parser = new Parser(new Lexer($keywords));
    }

    public function testTranslations()
    {
        $finder     = new Finder();
        $iterator   = $finder->files()->name('*.xliff')->in(__DIR__ . '/../../../i18n');

        foreach ($iterator as $file) {
            $transId    = basename($file, '.xliff');
            $created    = $this->createFeature($transId);
            $parsed     = $this->parseFeature($transId);

            $this->assertEquals($created, $parsed, $transId);
        }
    }

    protected function createFeature($locale)
    {
        $line = 2;
        $feature = new Node\FeatureNode('Some feature', "With some\ntext", null, $line);
        $feature->setLanguage($locale);
        $line += 2;

        $background = new Node\BackgroundNode($line += 2);
        foreach (explode('|', $this->trans('Given|When|Then|And|But', $locale)) as $type) {
            $step = new Node\StepNode($type, 'some background step', $line += 1);
            $background->addStep($step);
        }
        $feature->setBackground($background);

        for ($i = 0; $i < count(explode('|', $this->trans('Scenario', $locale))); $i++) { 
            $scenario = new Node\ScenarioNode('Scenario ' . $i, $line += 2);

            foreach (explode('|', $this->trans('Given|When|Then|And|But', $locale)) as $type) {
                $step = new Node\StepNode($type, 'some scenario step', $line += 1);
                $scenario->addStep($step);
            }

            $feature->addScenario($scenario);
        }

        return array($feature);
    }

    protected function parseFeature($locale)
    {
        $feature_kw = explode('|', $this->trans('Feature', $locale));
        $feature_kw = $feature_kw[0];
        $background_kw = explode('|', $this->trans('Background', $locale));
        $background_kw = $background_kw[0];

        $feature = <<<FEATURE
# language: $locale
$feature_kw: Some feature
  With some
  text

  $background_kw:

FEATURE;

        foreach (explode('|', $this->trans('Given|When|Then|And|But', $locale)) as $step_kw) {
            $feature .= <<<STEP
$step_kw some background step

STEP;
        }
        $feature .= "\n";

        foreach (explode('|', $this->trans('Scenario', $locale)) as $num => $scenario_kw) {
            $feature .= <<<SCENARIO
  $scenario_kw: Scenario $num

SCENARIO;
            foreach (explode('|', $this->trans('Given|When|Then|And|But', $locale)) as $step_kw) {
                $feature .= <<<STEP
    $step_kw some scenario step

STEP;
            }

            $feature .= "\n";
        }

        return $this->parser->parse($feature);
    }

    protected function trans($keyword, $locale)
    {
        return $this->translator->trans($keyword, array(), 'gherkin', $locale);
    }
}
