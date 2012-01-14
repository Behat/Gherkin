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
        $keywords = '';
        if ('en' !== $language) {
            $keywords = "# language: $language\n";
        }

        $keyword = $this->prepareKeyword($this->keywords->getFeatureKeywords());
        $keywords .= "$keyword: Internal operations";
        $keywords .= "\n  In order to stay secret\n  As a secret organization\n  We need to be able to erase past agents memory\n";

        $keyword = $this->prepareKeyword($this->keywords->getBackgroundKeywords());
        $keywords .= "\n  $keyword:";

        $stepKeyword = $this->prepareKeyword($this->keywords->getGivenKeywords());
        $scenarioStepKeywords  = "\n    $stepKeyword there is some agent <agent1>";
        $stepKeyword = $this->prepareKeyword($this->keywords->getAndKeywords());
        $scenarioStepKeywords .= "\n    $stepKeyword there is some agent <agent2>";

        $backgroundStepKeywords = strtr($scenarioStepKeywords, array(
            '<agent1>' => 'A',
            '<agent2>' => 'B'
        ))."\n";

        $stepKeyword = $this->prepareKeyword($this->keywords->getWhenKeywords());
        $scenarioStepKeywords .= "\n    $stepKeyword I erase agent <agent2> memory";
        $stepKeyword = $this->prepareKeyword($this->keywords->getThenKeywords());
        $scenarioStepKeywords .= "\n    $stepKeyword there should be agent <agent1>";
        $stepKeyword = $this->prepareKeyword($this->keywords->getButKeywords());
        $scenarioStepKeywords .= "\n    $stepKeyword there should not be agent <agent2>\n";

        $keywords .= $backgroundStepKeywords;

        $keyword = $this->prepareKeyword($this->keywords->getScenarioKeywords());
        $keywords .= "\n  $keyword: Erasing agent memory";
        $keywords .= strtr($scenarioStepKeywords, array(
            '<agent1>' => 'J',
            '<agent2>' => 'K'
        ));

        $keyword = $this->prepareKeyword($this->keywords->getOutlineKeywords());
        $keywords .= "\n  $keyword: Erasing other agents memory";
        $keywords .= $scenarioStepKeywords;

        $keyword = $this->prepareKeyword($this->keywords->getExamplesKeywords());
        $keywords .= "\n    $keyword:";
        $keywords .= "\n      | agent1 | agent2 |";
        $keywords .= "\n      | D      | M      |";

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
