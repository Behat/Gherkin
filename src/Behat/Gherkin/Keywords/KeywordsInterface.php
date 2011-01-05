<?php

namespace Behat\Gherkin\Keywords;

interface KeywordsInterface
{
    function setLanguage($language);

    function getFeatureKeywords();
    function getBackgroundKeywords();
    function getScenarioKeywords();
    function getOutlineKeywords();
    function getExamplesKeywords();
    function getStepKeywords();
}
