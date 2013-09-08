<?php

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CucumberKeywords;
use Behat\Gherkin\Node;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\Yaml\Yaml;

require_once 'KeywordsTest.php';

class CucumberKeywordsTest extends KeywordsTest
{
    protected function getKeywords()
    {
        return new CucumberKeywords(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected function getKeywordsArray()
    {
        return Yaml::parse(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected function getSteps($keywords, $text, &$line)
    {
        $steps = array();
        foreach (explode('|', mb_substr($keywords, 2)) as $keyword) {
            if (false !== mb_strpos($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, array(), $line++);
        }

        return $steps;
    }
}
