<?php

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Finder\Finder,
    Symfony\Component\Translation\Translator,
    Symfony\Component\Translation\Loader\XliffFileLoader;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Symfony Translation Component's keywords holder.
 *
 * $translator = new Symfony\Component\Translation\Translator('en', new Symfony\Component\Translation\MessageSelector());
 * $translator->addLoader(...);
 * $translator->addResource(...);
 * ...
 * $translator->addResource(...);
 *
 * $keywords = new Behat\Gherkin\Keywords\SymfonyTranslationKeywords($translator);
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SymfonyTranslationKeywords implements KeywordsInterface
{
    private $translator;
    private $locale = 'en';

    /**
     * Initializes keywords holder.
     *
     * @param Translator $translator Translator instance
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets keywords holder language.
     *
     * @param string $language Language name
     */
    public function setLanguage($language)
    {
        $this->locale = $language;
    }

    /**
     * Returns Feature keywords (splitted by "|").
     *
     * @return string
     */
    public function getFeatureKeywords()
    {
        return $this->translator->trans('Feature', array(), 'gherkin', $this->locale);
    }

    /**
     * Returns Background keywords (splitted by "|").
     *
     * @return string
     */
    public function getBackgroundKeywords()
    {
        return $this->translator->trans('Background', array(), 'gherkin', $this->locale);
    }

    /**
     * Returns Scenario keywords (splitted by "|").
     *
     * @return string
     */
    public function getScenarioKeywords()
    {
        return $this->translator->trans('Scenario', array(), 'gherkin', $this->locale);
    }

    /**
     * Returns Scenario Outline keywords (splitted by "|").
     *
     * @return string
     */
    public function getOutlineKeywords()
    {
        return $this->translator->trans('Scenario Outline', array(), 'gherkin', $this->locale);
    }

    /**
     * Returns Examples keywords (splitted by "|").
     *
     * @return string
     */
    public function getExamplesKeywords()
    {
        return $this->translator->trans('Examples', array(), 'gherkin', $this->locale);
    }

    /**
     * Returns Given keywords (splitted by "|").
     *
     * @return string
     */
    public function getGivenKeywords()
    {
    }

    /**
     * Returns When keywords (splitted by "|").
     *
     * @return string
     */
    public function getWhenKeywords()
    {
    }

    /**
     * Returns Then keywords (splitted by "|").
     *
     * @return string
     */
    public function getThenKeywords()
    {
    }

    /**
     * Returns And keywords (splitted by "|").
     *
     * @return string
     */
    public function getAndKeywords()
    {
    }

    /**
     * Returns But keywords (splitted by "|").
     *
     * @return string
     */
    public function getButKeywords()
    {
    }

    /**
     * Returns all step keywords (splitted by "|").
     *
     * @return string
     */
    public function getStepKeywords()
    {
        return $this->translator->trans('Given|When|Then|And|But', array(), 'gherkin', $this->locale);
    }
}
