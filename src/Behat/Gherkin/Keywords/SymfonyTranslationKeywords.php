<?php

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Translation\Translator;

class SymfonyTranslationKeywords implements KeywordsInterface
{
    private $translator;
    private $locale = 'en';

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function setLanguage($language)
    {
        $this->locale = $language;
    }

    public function getFeatureKeywords()
    {
        return $this->translator->trans('Feature', array(), 'gherkin', $this->locale);
    }

    public function getBackgroundKeywords()
    {
        return $this->translator->trans('Background', array(), 'gherkin', $this->locale);
    }

    public function getScenarioKeywords()
    {
        return $this->translator->trans('Scenario', array(), 'gherkin', $this->locale);
    }

    public function getOutlineKeywords()
    {
        return $this->translator->trans('Scenario Outline', array(), 'gherkin', $this->locale);
    }

    public function getExamplesKeywords()
    {
        return $this->translator->trans('Examples', array(), 'gherkin', $this->locale);
    }

    public function getStepKeywords()
    {
        return $this->translator->trans('Given|When|Then|And|But', array(), 'gherkin', $this->locale);
    }
}
