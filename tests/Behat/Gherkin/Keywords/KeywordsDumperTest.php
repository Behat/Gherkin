<?php

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\ArrayKeywords,
    Behat\Gherkin\Keywords\KeywordsDumper;

class KeywordsDumperTest extends \PHPUnit_Framework_TestCase
{
    private $keywords;

    protected function setUp()
    {
        $this->keywords = new ArrayKeywords(array(
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
           ),
           'ru' => array(
               'feature'          => 'Функционал|Фича',
               'background'       => 'Предыстория|Бэкграунд',
               'scenario'         => 'Сценарий|История',
               'scenario_outline' => 'Структура сценария|Аутлайн',
               'examples'         => 'Значения',
               'given'            => 'Допустим',
               'when'             => 'Если',
               'then'             => 'То',
               'and'              => 'И',
               'but'              => 'Но'
           )
        ));
    }

    public function testEnKeywordsDumper()
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('en');
        $etalon = <<<GHERKIN
Feature: Internal operations
  In order to stay secret
  As a secret organization
  We need to be able to erase past agents memory

  Background:
    Given there is some agent A
    And there is some agent B

  Scenario: Erasing agent memory
    Given there is some agent J
    And there is some agent K
    When I erase agent K memory
    Then there should be agent J
    But there should not be agent K

  (Scenario Outline|Scenario Template): Erasing other agents memory
    Given there is some agent <agent1>
    And there is some agent <agent2>
    When I erase agent <agent2> memory
    Then there should be agent <agent1>
    But there should not be agent <agent2>

    (Examples|Scenarios):
      | agent1 | agent2 |
      | D      | M      |
GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }

    public function testRuKeywordsDumper()
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('ru');
        $etalon = <<<GHERKIN
# language: ru
(Функционал|Фича): Internal operations
  In order to stay secret
  As a secret organization
  We need to be able to erase past agents memory

  (Предыстория|Бэкграунд):
    Допустим there is some agent A
    И there is some agent B

  (Сценарий|История): Erasing agent memory
    Допустим there is some agent J
    И there is some agent K
    Если I erase agent K memory
    То there should be agent J
    Но there should not be agent K

  (Структура сценария|Аутлайн): Erasing other agents memory
    Допустим there is some agent <agent1>
    И there is some agent <agent2>
    Если I erase agent <agent2> memory
    То there should be agent <agent1>
    Но there should not be agent <agent2>

    Значения:
      | agent1 | agent2 |
      | D      | M      |
GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }
}
