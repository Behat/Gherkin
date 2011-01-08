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
 * 
 * $keywords = new Behat\Gherkin\Keywords\SymfonyTranslationKeywords($translator);
 * $keywords->setXliffTranslationsPath('/path/to/xliff/translations');
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SymfonyTranslationKeywords implements KeywordsInterface
{
    private $translator;
    private $locale = 'en';
    private $xliffLoaderFormatName;

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
     * Set loader format name to use for XLIFF files.
     *
     * @param   string  $format
     */
    public function setXliffLoaderFormatName($format)
    {
        $this->xliffLoaderFormatName = $format;
    }

    /**
     * Tell Translator to read XLIFF translations from specified path.
     *
     * @param   string  $path
     */
    public function setXliffTranslationsPath($path)
    {
        if (null === $this->xliffLoaderFormatName) {
            $this->xliffLoaderFormatName = 'xliff';
            $this->translator->addLoader($this->xliffLoaderFormatName, new XliffFileLoader());
        }

        $finder     = new Finder();
        $iterator   = $finder->files()->name('*.xliff')->in($path);

        foreach ($iterator as $file) {
            $transId = basename($file, '.xliff');
            $this->translator->addResource($this->xliffLoaderFormatName, $file, $transId, 'gherkin');
        }
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
