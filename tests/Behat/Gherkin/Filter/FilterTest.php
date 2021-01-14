<?php

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

abstract class FilterTest extends TestCase
{
    protected function getParser()
    {
        return new Parser(
            new Lexer(
                new ArrayKeywords(array(
                    'en' => array(
                        'feature'          => 'Feature',
                        'background'       => 'Background',
                        'scenario'         => 'Scenario',
                        'scenario_outline' => 'Scenario Outline|Scenario Template',
                        'examples'         => 'Examples|Scenarios',
                        'given'            => 'Given',
                        'when'             => 'When',
                        'then'             => 'Then',
                        'and'              => 'And',
                        'but'              => 'But'
                    )
                ))
            )
        );
    }

    protected function getGherkinFeature()
    {
        return <<<GHERKIN
Feature: Long feature with outline
  Scenario: Scenario#1
    Given initial step
    When action occurs
    Then outcomes should be visible

  Scenario: Scenario#2
    Given initial step
    And another initial step
    When action occurs
    Then outcomes should be visible

  Scenario Outline: Scenario#3
    When <action> occurs
    Then <outcome> should be visible

    @etag1
    Examples:
      | action | outcome |
      | act#1  | out#1   |
      | act#2  | out#2   |
      
    @etag2
    Examples:
      | action | outcome |
      | act#3  | out#3   |

GHERKIN;
    }

    protected function getParsedFeature()
    {
        return $this->getParser()->parse($this->getGherkinFeature());
    }
}
