<?php

namespace Behat\Gherkin\Keywords;

use Symfony\Component\Translation\Translator;

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
 * $translator->addLoader('xliff', new Symfony\Component\Translation\Loader\XliffFileLoader());
 * $translator->addResource('xliff', $path, $id, 'gherkin');
 * 
 * $keywords = new Behat\Gherkin\Keywords\SymfonyTranslationKeywords($translator);
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SymfonyTranslationKeywords implements KeywordsInterface
{
    private $translator;
    private $locale = 'en';

    /**
     * Initialize keywords holder.
     *
     * @param   Translator  $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguage($language)
    {
        $this->locale = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatureKeywords()
    {
        return $this->translator->trans('Feature', array(), 'gherkin', $this->locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getBackgroundKeywords()
    {
        return $this->translator->trans('Background', array(), 'gherkin', $this->locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getScenarioKeywords()
    {
        return $this->translator->trans('Scenario', array(), 'gherkin', $this->locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getOutlineKeywords()
    {
        return $this->translator->trans('Scenario Outline', array(), 'gherkin', $this->locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getExamplesKeywords()
    {
        return $this->translator->trans('Examples', array(), 'gherkin', $this->locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getStepKeywords()
    {
        return $this->translator->trans('Given|When|Then|And|But', array(), 'gherkin', $this->locale);
    }
}
