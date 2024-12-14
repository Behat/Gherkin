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
 * Keywords holder interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface KeywordsInterface
{
    /**
     * Sets keywords holder language.
     *
     * @param string $language Language name
     */
    public function setLanguage($language);

    /**
     * Returns Feature keywords (separated by "|").
     *
     * @return string
     */
    public function getFeatureKeywords();

    /**
     * Returns Background keywords (separated by "|").
     *
     * @return string
     */
    public function getBackgroundKeywords();

    /**
     * Returns Scenario keywords (separated by "|").
     *
     * @return string
     */
    public function getScenarioKeywords();

    /**
     * Returns Scenario Outline keywords (separated by "|").
     *
     * @return string
     */
    public function getOutlineKeywords();

    /**
     * Returns Examples keywords (separated by "|").
     *
     * @return string
     */
    public function getExamplesKeywords();

    /**
     * Returns Given keywords (separated by "|").
     *
     * @return string
     */
    public function getGivenKeywords();

    /**
     * Returns When keywords (separated by "|").
     *
     * @return string
     */
    public function getWhenKeywords();

    /**
     * Returns Then keywords (separated by "|").
     *
     * @return string
     */
    public function getThenKeywords();

    /**
     * Returns And keywords (separated by "|").
     *
     * @return string
     */
    public function getAndKeywords();

    /**
     * Returns But keywords (separated by "|").
     *
     * @return string
     */
    public function getButKeywords();

    /**
     * Returns all step keywords (separated by "|").
     *
     * @return string
     */
    public function getStepKeywords();
}
