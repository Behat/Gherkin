<?php

namespace Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\KeywordsInterface;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin keywords dumper.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class KeywordsDumper
{
    protected $keywords;

    /**
     * Initializes dumper.
     *
     * @param   Behat\Gherkin\Keywords\KeywordsInterface   $keywords
     */
    public function __construct(KeywordsInterface $keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * Dump keyworded feature into string.
     *
     * @param   string  $language   keywords language
     * @param   Boolean $short      dump short version
     *
     * @return  string
     */
    public function dump($language, $short = true)
    {
        $this->keywords->setLanguage($language);
        $dump = '';
        if ('en' !== $language) {
            $dump = "# language: $language\n";
        }

        $keywords = $this->keywords->getFeatureKeywords();
        if ($short) {
            $dump .= $this->dumpFeature($this->prepareKeyword($keywords), $short);
        } else {
            foreach (explode('|', $keywords) as $keyword) {
                $dump .= $this->dumpFeature($keyword, $short);
            }
        }

        return trim($dump);
    }

    /**
     * Dumps feature example.
     *
     * @param   string  $keyword    item keyword
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpFeature($keyword, $short = true)
    {
        $dump = <<<GHERKIN
{$keyword}: Internal operations
  In order to stay secret
  As a secret organization
  We need to be able to erase past agents memory


GHERKIN;

        // Background
        $keywords = $this->keywords->getBackgroundKeywords();
        if ($short) {
            $dump .= $this->dumpBackground($this->prepareKeyword($keywords), $short);
        } else {
            $keywords = explode('|', $keywords);
            $dump .= $this->dumpBackground($keywords[0], $short);
        }

        // Scenario
        $keywords = $this->keywords->getScenarioKeywords();
        if ($short) {
            $dump .= $this->dumpScenario($this->prepareKeyword($keywords), $short);
        } else {
            foreach (explode('|', $keywords) as $keyword) {
                $dump .= $this->dumpScenario($keyword, $short);
            }
        }

        // Outline
        $keywords = $this->keywords->getOutlineKeywords();
        if ($short) {
            $dump .= $this->dumpOutline($this->prepareKeyword($keywords), $short);
        } else {
            foreach (explode('|', $keywords) as $keyword) {
                $dump .= $this->dumpOutline($keyword, $short);
            }
        }

        return $dump;
    }

    /**
     * Dumps background example.
     *
     * @param   string  $keyword    item keyword
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpBackground($keyword, $short = true)
    {
        $dump = <<<GHERKIN
  {$keyword}:

GHERKIN;

        // Given
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getGivenKeywords(), 'there is agent A'
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent B'
        );

        return $dump."\n";
    }

    /**
     * Dumps scenario example.
     *
     * @param   string  $keyword    item keyword
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpScenario($keyword, $short = true)
    {
        $dump = <<<GHERKIN
  {$keyword}: Erasing agent memory

GHERKIN;

        // Given
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getGivenKeywords(), 'there is agent J'
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent K'
        );

        // When
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getWhenKeywords(), 'I erase agent K memory'
        );

        // Then
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getThenKeywords(), 'there should be agent J'
        );

        // But
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getButKeywords(), 'there should not be agent K'
        );

        return $dump."\n";
    }

    /**
     * Dumps outline example.
     *
     * @param   string  $keyword    item keyword
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpOutline($keyword, $short = true)
    {
        $dump = <<<GHERKIN
  {$keyword}: Erasing other agents memory

GHERKIN;

        // Given
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getGivenKeywords(), 'there is agent <agent1>'
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent <agent2>'
        );

        // When
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getWhenKeywords(), 'I erase agent <agent2> memory'
        );

        // Then
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getThenKeywords(), 'there should be agent <agent1>'
        );

        // But
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getButKeywords(), 'there should not be agent <agent2>'
        );

        $keywords = $this->keywords->getExamplesKeywords();
        if ($short) {
            $keyword = $this->prepareKeyword($keywords);
        } else {
            $keywords = explode('|', $keywords);
            $keyword  = $keywords[0];
        }
        $dump .= <<<GHERKIN

    {$keyword}:
      | agent1 | agent2 |
      | D      | M      |

GHERKIN;

        return $dump."\n";
    }

    /**
     * Dumps step keywords example.
     *
     * @param   string  $keyword    keywords list (splitted with "|")
     * @param   string  $text       step text
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpStepKeywords($keywords, $text, $short = true)
    {
        $dump = '';
        if ($short) {
            $dump .= $this->dumpStep($this->prepareKeyword($keywords), $text, $short);
        } else {
            foreach (explode('|', $keywords) as $keyword) {
                $dump .= $this->dumpStep($keyword, $text, $short);
            }
        }

        return $dump;
    }

    /**
     * Dumps step example.
     *
     * @param   string  $keyword    item keyword
     * @param   string  $text       step text
     * @param   Boolean $short      dump short version?
     *
     * @return  string
     */
    protected function dumpStep($keyword, $text, $short = true)
    {
        $dump = <<<GHERKIN
    {$keyword} {$text}
GHERKIN;

        return $dump."\n";
    }

    /**
     * Wrap keyword with "(", ")" if there's multiple variants.
     *
     * @param   string  $keyword
     *
     * @return  string
     */
    protected function prepareKeyword($keyword)
    {
        return false !== mb_strpos($keyword, '|') ? "($keyword)" : $keyword;
    }
}
