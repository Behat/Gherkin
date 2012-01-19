<?php

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Node,
    Behat\Gherkin\Keywords\KeywordsDumper,
    Behat\Gherkin\Keywords\CucumberKeywords;

use Symfony\Component\Yaml\Yaml;

require_once 'KeywordsTest.php';

class CucumberKeywordsTest extends KeywordsTest
{
    /**
     * Returns Keywords instance.
     *
     * @return KeywordsInterface
     */
    protected function getKeywords()
    {
        return new CucumberKeywords(__DIR__.'/../Fixtures/i18n.yml');
    }

    /**
     * Returns array of keywords.
     *
     * @return array
     */
    protected function getKeywordsArray()
    {
        return Yaml::parse(__DIR__.'/../Fixtures/i18n.yml');
    }

    /**
     * Adds steps to the scenario/background/outline.
     *
     * @param   Node\AbstractScenarioNode $scenario
     * @param   string                    $keywords
     * @param   string                    $text
     * @param   integer                   $line
     *
     * @return  integer
     */
    protected function addSteps(Node\AbstractScenarioNode $scenario, $keywords, $text, $line)
    {
        foreach (explode('|', mb_substr($keywords, 2)) as $keyword) {
            if (false !== mb_strpos($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }
            $scenario->addStep(new Node\StepNode($keyword, $text, $line));
            $line += 1;
        }

        return $line;
    }
}
