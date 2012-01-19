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
 * Keywords holder interface.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface KeywordsInterface
{
    /**
     * Sets keywords holder language.
     *
     * @param   string  $language
     */
    function setLanguage($language);

    /**
     * Returns Feature keywords (splitted by "|").
     *
     * @return  string
     */
    function getFeatureKeywords();

    /**
     * Returns Background keywords (splitted by "|").
     *
     * @return  string
     */
    function getBackgroundKeywords();

    /**
     * Returns Scenario keywords (splitted by "|").
     *
     * @return  string
     */
    function getScenarioKeywords();

    /**
     * Returns Scenario Outline keywords (splitted by "|").
     *
     * @return  string
     */
    function getOutlineKeywords();

    /**
     * Returns Examples keywords (splitted by "|").
     *
     * @return  string
     */
    function getExamplesKeywords();

    /**
     * Returns Given keywords (splitted by "|")
     *
     * @return  string
     */
    function getGivenKeywords();

    /**
     * Returns When keywords (splitted by "|")
     *
     * @return  string
     */
    function getWhenKeywords();

    /**
     * Returns Then keywords (splitted by "|")
     *
     * @return  string
     */
    function getThenKeywords();

    /**
     * Returns And keywords (splitted by "|")
     *
     * @return  string
     */
    function getAndKeywords();

    /**
     * Returns But keywords (splitted by "|")
     *
     * @return  string
     */
    function getButKeywords();

    /**
     * Returns all step keywords (splitted by "|").
     *
     * @return  string
     */
    function getStepKeywords();
}
