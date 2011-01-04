<?php

namespace Behat\Gherkin\Keywords;

interface KeywordsInterface
{
    function setLanguage($language);

    function getFeatureKeyword();
    function getBackgroundKeyword();
    function getScenarioKeyword();
    function getOutlineKeyword();
    function getExamplesKeyword();
    function getStepKeywords();
}
