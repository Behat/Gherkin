<?php

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CachedArrayKeywords,
    Behat\Gherkin\Node;

require_once 'KeywordsTest.php';

class CachedArrayKeywordsTest extends KeywordsTest
{
    protected function getKeywords()
    {
        return new CachedArrayKeywords(__DIR__.'/../../../../i18n.php');
    }

    protected function getKeywordsArray()
    {
        return include(__DIR__.'/../../../../i18n.php');
    }

    protected function addSteps(Node\AbstractScenarioNode $scenario, $keywords, $text, $line)
    {
        foreach (explode('|', $keywords) as $keyword) {
            if (false !== mb_strpos($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }
            $scenario->addStep(new Node\StepNode($keyword, $text, $line));
            $line += 1;
        }

        return $line;
    }
}
