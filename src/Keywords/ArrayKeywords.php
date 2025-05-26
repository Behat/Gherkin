<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Keywords;

/**
 * Array initializable keywords holder.
 *
 * ```
 * $keywords = new Behat\Gherkin\Keywords\ArrayKeywords(array(
 *     'en' => array(
 *         'feature'          => 'Feature',
 *         'background'       => 'Background',
 *         'scenario'         => 'Scenario',
 *         'scenario_outline' => 'Scenario Outline|Scenario Template',
 *         'examples'         => 'Examples|Scenarios',
 *         'given'            => 'Given',
 *         'when'             => 'When',
 *         'then'             => 'Then',
 *         'and'              => 'And',
 *         'but'              => 'But'
 *     ),
 *     'ru' => array(
 *         'feature'          => 'Функционал',
 *         'background'       => 'Предыстория',
 *         'scenario'         => 'Сценарий',
 *         'scenario_outline' => 'Структура сценария',
 *         'examples'         => 'Примеры',
 *         'given'            => 'Допустим',
 *         'when'             => 'Если',
 *         'then'             => 'То',
 *         'and'              => 'И',
 *         'but'              => 'Но'
 *     )
 * ));
 * ```
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TKeywordsArray array{
 *      feature: string,
 *      background: string,
 *      scenario: string,
 *      scenario_outline: string,
 *      examples: string,
 *      given: string,
 *      when: string,
 *      then: string,
 *      and: string,
 *      but: string,
 *  }
 * @phpstan-type TMultiLanguageKeywords array<string, TKeywordsArray>
 */
class ArrayKeywords implements KeywordsInterface
{
    /**
     * @var array<string, string>
     */
    private array $keywordString = [];
    private string $language = 'en';

    /**
     * Initializes holder with keywords.
     *
     * @phpstan-param TMultiLanguageKeywords $keywords Keywords array
     */
    public function __construct(
        private readonly array $keywords,
    ) {
    }

    /**
     * Sets keywords holder language.
     *
     * @return void
     */
    public function setLanguage(string $language)
    {
        if (!isset($this->keywords[$language])) {
            $this->language = 'en';
        } else {
            $this->language = $language;
        }
    }

    /**
     * @internal
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Returns Feature keywords (separated by "|").
     *
     * @return string
     */
    public function getFeatureKeywords()
    {
        return $this->keywords[$this->language]['feature'];
    }

    /**
     * Returns Background keywords (separated by "|").
     *
     * @return string
     */
    public function getBackgroundKeywords()
    {
        return $this->keywords[$this->language]['background'];
    }

    /**
     * Returns Scenario keywords (separated by "|").
     *
     * @return string
     */
    public function getScenarioKeywords()
    {
        return $this->keywords[$this->language]['scenario'];
    }

    /**
     * Returns Scenario Outline keywords (separated by "|").
     *
     * @return string
     */
    public function getOutlineKeywords()
    {
        return $this->keywords[$this->language]['scenario_outline'];
    }

    /**
     * Returns Examples keywords (separated by "|").
     *
     * @return string
     */
    public function getExamplesKeywords()
    {
        return $this->keywords[$this->language]['examples'];
    }

    /**
     * Returns Given keywords (separated by "|").
     *
     * @return string
     */
    public function getGivenKeywords()
    {
        return $this->keywords[$this->language]['given'];
    }

    /**
     * Returns When keywords (separated by "|").
     *
     * @return string
     */
    public function getWhenKeywords()
    {
        return $this->keywords[$this->language]['when'];
    }

    /**
     * Returns Then keywords (separated by "|").
     *
     * @return string
     */
    public function getThenKeywords()
    {
        return $this->keywords[$this->language]['then'];
    }

    /**
     * Returns And keywords (separated by "|").
     *
     * @return string
     */
    public function getAndKeywords()
    {
        return $this->keywords[$this->language]['and'];
    }

    /**
     * Returns But keywords (separated by "|").
     *
     * @return string
     */
    public function getButKeywords()
    {
        return $this->keywords[$this->language]['but'];
    }

    /**
     * Returns all step keywords (Given, When, Then, And, But).
     *
     * @return string
     */
    public function getStepKeywords()
    {
        if (!isset($this->keywordString[$this->language])) {
            $keywords = array_merge(
                explode('|', $this->getGivenKeywords()),
                explode('|', $this->getWhenKeywords()),
                explode('|', $this->getThenKeywords()),
                explode('|', $this->getAndKeywords()),
                explode('|', $this->getButKeywords())
            );

            usort($keywords, function ($keyword1, $keyword2) {
                return mb_strlen($keyword2, 'utf8') - mb_strlen($keyword1, 'utf8');
            });

            $this->keywordString[$this->language] = implode('|', $keywords);
        }

        return $this->keywordString[$this->language];
    }
}
