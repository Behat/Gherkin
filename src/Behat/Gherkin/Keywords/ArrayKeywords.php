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

    public function getFeatureKeyword()
    {
        return $this->keywords[$this->language]['Feature'];
    }

    public function getBackgroundKeyword()
    {
        return $this->keywords[$this->language]['Background'];
    }

    public function getScenarioKeyword()
    {
        return $this->keywords[$this->language]['Scenario'];
    }

    public function getOutlineKeyword()
    {
        return $this->keywords[$this->language]['Scenario Outline'];
    }

    public function getExamplesKeyword()
    {
        return $this->keywords[$this->language]['Examples'];
    }

    public function getStepKeywords()
    {
        return $this->keywords[$this->language]['Step Types'];
    }
}
