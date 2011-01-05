<?php

namespace Behat\Gherkin\Keywords;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Array initializable keywords holder.
 * 
 * $keywords = new Behat\Gherkin\Keywords\ArrayKeywords(array(
 *     'en' => array(
 *         'Feature'           => 'Feature',
 *         'Background'        => 'Background',
 *         'Scenario'          => 'Scenario',
 *         'Scenario Outline'  => 'Scenario Outline',
 *         'Examples'          => 'Examples',
 *         'Step Types'        => 'Given|When|Then|And|But'
 *     ),
 *     'ru' => array(
 *         'Feature'           => 'Функционал',
 *         'Background'        => 'Предыстория',
 *         'Scenario'          => 'Сценарий',
 *         'Scenario Outline'  => 'Структура сценария',
 *         'Examples'          => 'Значения',
 *         'Step Types'        => 'Допустим|То|Если|И|Но'
 *     )
 * ));
 * 
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ArrayKeywords implements KeywordsInterface
{
    private $keywords = array();
    private $language;

    /**
     * Initialize holder with keywords.
     *
     * @param   array   $keywords
     */
    public function __construct(array $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatureKeywords()
    {
        return $this->keywords[$this->language]['Feature'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBackgroundKeywords()
    {
        return $this->keywords[$this->language]['Background'];
    }

    /**
     * {@inheritdoc}
     */
    public function getScenarioKeywords()
    {
        return $this->keywords[$this->language]['Scenario'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOutlineKeywords()
    {
        return $this->keywords[$this->language]['Scenario Outline'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExamplesKeywords()
    {
        return $this->keywords[$this->language]['Examples'];
    }

    /**
     * {@inheritdoc}
     */
    public function getStepKeywords()
    {
        return $this->keywords[$this->language]['Step Types'];
    }
}
