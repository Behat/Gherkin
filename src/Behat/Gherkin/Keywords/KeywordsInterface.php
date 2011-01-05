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
     * Set keywords holder language.
     * 
     * @param   string  $language
     */
    function setLanguage($language);

    /**
     * Return Feature keywords (splitted by "|").
     *
     * @return  string
     */
    function getFeatureKeywords();

    /**
     * Return Background keywords (splitted by "|").
     *
     * @return  string
     */
    function getBackgroundKeywords();

    /**
     * Return Scenario keywords (splitted by "|").
     *
     * @return  string
     */
    function getScenarioKeywords();

    /**
     * Return Scenario Outline keywords (splitted by "|").
     *
     * @return  string
     */
    function getOutlineKeywords();

    /**
     * Return Examples keywords (splitted by "|").
     *
     * @return  string
     */
    function getExamplesKeywords();

    /**
     * Return Step keywords (splitted by "|").
     *
     * @return  string
     */
    function getStepKeywords();
}
