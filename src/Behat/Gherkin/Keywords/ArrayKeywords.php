<?php

namespace Behat\Gherkin\Keywords;

class ArrayKeywords implements KeywordsInterface
{
    private $keywords = array();
    private $language;

    public function __construct(array $keywords)
    {
        $this->keywords = $keywords;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getFeatureKeywords()
    {
        return $this->keywords[$this->language]['Feature'];
    }

    public function getBackgroundKeywords()
    {
        return $this->keywords[$this->language]['Background'];
    }

    public function getScenarioKeywords()
    {
        return $this->keywords[$this->language]['Scenario'];
    }

    public function getOutlineKeywords()
    {
        return $this->keywords[$this->language]['Scenario Outline'];
    }

    public function getExamplesKeywords()
    {
        return $this->keywords[$this->language]['Examples'];
    }

    public function getStepKeywords()
    {
        return $this->keywords[$this->language]['Step Types'];
    }
}
