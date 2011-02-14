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
     *
     * @return  string
     */
    public function dump($language)
    {
        $this->keywords->setLanguage($language);
        $keywords  = "# language: $language";

        $keyword = $this->prepareKeyword($this->keywords->getFeatureKeywords());
        $keywords .= "\n$keyword: feature title";
        $keywords .= "\n  In order to ...\n  As a ...\n  I need to ...\n";

        $keyword = $this->prepareKeyword($this->keywords->getBackgroundKeywords());
        $keywords .= "\n  $keyword:";

        $stepKeyword = '(' . $this->keywords->getStepKeywords() . ')';
        $stepKeywords  = "\n    $stepKeyword step 1";
        $stepKeywords .= "\n    $stepKeyword step 2\n";
        $keywords .= $stepKeywords;

        $keyword = $this->prepareKeyword($this->keywords->getScenarioKeywords());
        $keywords .= "\n  $keyword: scenario title";
        $keywords .= $stepKeywords;

        $keyword = $this->prepareKeyword($this->keywords->getOutlineKeywords());
        $keywords .= "\n  $keyword: outline title";
        $keywords .= "\n    $stepKeyword step <val1>";
        $keywords .= "\n    $stepKeyword step <val2>\n";

        $keyword = $this->prepareKeyword($this->keywords->getExamplesKeywords());
        $keywords .= "\n    $keyword:";
        $keywords .= "\n      | val1 | val2 |";
        $keywords .= "\n      | 23   | 122  |";

        return $keywords;
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
