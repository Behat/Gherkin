<?php

namespace Behat\Gherkin\Keywords;

class EnglishKeywords implements KeywordsInterface
{
    public function setLanguage($language)
    {}

    public function getFeatureKeyword()
    {
        return 'Feature';
    }

    public function getBackgroundKeyword()
    {
        return 'Background';
    }

    public function getScenarioKeyword()
    {
        return 'Scenario';
    }

    public function getOutlineKeyword()
    {
        return 'Scenario Outline';
    }

    public function getExamplesKeyword()
    {
        return 'Examples';
    }

    public function getStepKeywords()
    {
        return array('Given', 'When', 'Then', 'And', 'Or');
    }
}
