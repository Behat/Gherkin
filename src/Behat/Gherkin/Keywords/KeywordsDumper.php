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
    private $keywords;
    private $keywordsDumper;

    /**
     * Initializes dumper.
     *
     * @param   Behat\Gherkin\Keywords\KeywordsInterface   $keywords
     */
    public function __construct(KeywordsInterface $keywords)
    {
        $this->keywords = $keywords;
        $this->keywordsDumper = array($this, 'dumpKeywords');
    }

    /**
     * Sets keywords mapper function.
     *
     * Callable should accept 2 arguments (array $keywords and Boolean $isShort)
     *
     * @param callable $mapper
     */
    public function setKeywordsDumperFunction($mapper)
    {
        $this->keywordsDumper = $mapper;
    }

    /**
     * Defaults keywords dumper.
     *
     * @param   array   $keywords keywords list
     * @param   Boolean $isShort  is short version
     *
     * @return  string
     */
    public function dumpKeywords(array $keywords, $isShort)
    {
        if ($isShort) {
            return 1 < count($keywords) ? '('.implode('|', $keywords).')' : $keywords[0];
        }

        return $keywords[0];
    }

    /**
     * Dump keyworded feature into string.
     *
     * @param   string  $language   keywords language
     * @param   Boolean $short      dump short version
     *
     * @return  string|array        string for short version and array of features for extended
     */
    public function dump($language, $short = true)
    {
        $this->keywords->setLanguage($language);
        $languageComment = '';
        if ('en' !== $language) {
            $languageComment = "# language: $language\n";
        }

        $keywords = explode('|', $this->keywords->getFeatureKeywords());

        if ($short) {
            $keywords = call_user_func($this->keywordsDumper, $keywords, $short);

            return trim($languageComment.$this->dumpFeature($keywords, $short));
        }

        $features = array();
        foreach ($keywords as $keyword) {
            $keyword    = call_user_func($this->keywordsDumper, array($keyword), $short);
            $features[] = trim($languageComment.$this->dumpFeature($keyword, $short));
        }

        return $features;
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
  We need to be able to erase past agents' memory


GHERKIN;

        // Background
        $keywords = explode('|', $this->keywords->getBackgroundKeywords());
        if ($short) {
            $keywords = call_user_func($this->keywordsDumper, $keywords, $short);
            $dump    .= $this->dumpBackground($keywords, $short);
        } else {
            $keyword  = call_user_func($this->keywordsDumper, array($keywords[0]), $short);
            $dump .= $this->dumpBackground($keyword, $short);
        }

        // Scenario
        $keywords = explode('|', $this->keywords->getScenarioKeywords());
        if ($short) {
            $keywords = call_user_func($this->keywordsDumper, $keywords, $short);
            $dump    .= $this->dumpScenario($keywords, $short);
        } else {
            foreach ($keywords as $keyword) {
                $keyword = call_user_func($this->keywordsDumper, array($keyword), $short);
                $dump   .= $this->dumpScenario($keyword, $short);
            }
        }

        // Outline
        $keywords = explode('|', $this->keywords->getOutlineKeywords());
        if ($short) {
            $keywords = call_user_func($this->keywordsDumper, $keywords, $short);
            $dump    .= $this->dumpOutline($keywords, $short);
        } else {
            foreach ($keywords as $keyword) {
                $keyword = call_user_func($this->keywordsDumper, array($keyword), $short);
                $dump   .= $this->dumpOutline($keyword, $short);
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
            $this->keywords->getGivenKeywords(), 'there is agent A', $short
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent B', $short
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
            $this->keywords->getGivenKeywords(), 'there is agent J', $short
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent K', $short
        );

        // When
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getWhenKeywords(), 'I erase agent K\'s memory', $short
        );

        // Then
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getThenKeywords(), 'there should be agent J', $short
        );

        // But
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getButKeywords(), 'there should not be agent K', $short
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
  {$keyword}: Erasing other agents' memory

GHERKIN;

        // Given
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getGivenKeywords(), 'there is agent <agent1>', $short
        );

        // And
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getAndKeywords(), 'there is agent <agent2>', $short
        );

        // When
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getWhenKeywords(), 'I erase agent <agent2>\'s memory', $short
        );

        // Then
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getThenKeywords(), 'there should be agent <agent1>', $short
        );

        // But
        $dump .= $this->dumpStepKeywords(
            $this->keywords->getButKeywords(), 'there should not be agent <agent2>', $short
        );

        $keywords = explode('|', $this->keywords->getExamplesKeywords());
        if ($short) {
            $keyword = call_user_func($this->keywordsDumper, $keywords, $short);
        } else {
            $keyword = call_user_func($this->keywordsDumper, array($keywords[0]), $short);
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

        $keywords = explode('|', $keywords);
        if ($short) {
            $keywords = call_user_func($this->keywordsDumper, $keywords, $short);
            $dump    .= $this->dumpStep($keywords, $text, $short);
        } else {
            foreach ($keywords as $keyword) {
                $keyword = call_user_func($this->keywordsDumper, array($keyword), $short);
                $dump   .= $this->dumpStep($keyword, $text, $short);
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
        if (!$short && false !== mb_strpos($keyword, '<')) {
            $keyword = mb_substr($keyword, 0, -1);
        } else {
            $keyword = str_replace('<', '', $keyword).' ';
        }

        $dump = <<<GHERKIN
    {$keyword}{$text}
GHERKIN;

        return $dump."\n";
    }
}
