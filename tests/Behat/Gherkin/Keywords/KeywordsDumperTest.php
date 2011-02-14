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
                'Feature'           => 'Feature',
                'Background'        => 'Background',
                'Scenario'          => 'Scenario',
                'Scenario Outline'  => 'Scenario Outline',
                'Examples'          => 'Examples',
                'Step Types'        => 'Given|When|Then|And|But'
            ),
            'ru' => array(
                'Feature'           => 'Функционал|Фича',
                'Background'        => 'Предыстория|Бэкграунд',
                'Scenario'          => 'Сценарий|История',
                'Scenario Outline'  => 'Структура сценария|Аутлайн',
                'Examples'          => 'Значения',
                'Step Types'        => 'Допустим|То|Если|И|Но'
            )
        ));
    }

    public function testEnKeywordsDumper()
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('en');
        $etalon = <<<GHERKIN
# language: en
Feature: feature title
  In order to ...
  As a ...
  I need to ...

  Background:
    (Given|When|Then|And|But) step 1
    (Given|When|Then|And|But) step 2

  Scenario: scenario title
    (Given|When|Then|And|But) step 1
    (Given|When|Then|And|But) step 2

  Scenario Outline: outline title
    (Given|When|Then|And|But) step <val1>
    (Given|When|Then|And|But) step <val2>

    Examples:
      | val1 | val2 |
      | 23   | 122  |
GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }

    public function testRuKeywordsDumper()
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('ru');
        $etalon = <<<GHERKIN
# language: ru
(Функционал|Фича): feature title
  In order to ...
  As a ...
  I need to ...

  (Предыстория|Бэкграунд):
    (Допустим|То|Если|И|Но) step 1
    (Допустим|То|Если|И|Но) step 2

  (Сценарий|История): scenario title
    (Допустим|То|Если|И|Но) step 1
    (Допустим|То|Если|И|Но) step 2

  (Структура сценария|Аутлайн): outline title
    (Допустим|То|Если|И|Но) step <val1>
    (Допустим|То|Если|И|Но) step <val2>

    Значения:
      | val1 | val2 |
      | 23   | 122  |
GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }
}
