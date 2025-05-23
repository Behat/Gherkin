<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsDumper;
use PHPUnit\Framework\TestCase;

class KeywordsDumperTest extends TestCase
{
    private ArrayKeywords $keywords;

    protected function setUp(): void
    {
        $this->keywords = new ArrayKeywords([
            'en' => [
                'feature' => 'Feature',
                'background' => 'Background',
                'scenario' => 'Scenario',
                'scenario_outline' => 'Scenario Outline|Scenario Template',
                'examples' => 'Examples|Scenarios',
                'given' => 'Given',
                'when' => 'When',
                'then' => 'Then',
                'and' => 'And',
                'but' => 'But',
            ],
            'ru' => [
                'feature' => 'Функционал|Фича',
                'background' => 'Предыстория|Бэкграунд',
                'scenario' => 'Сценарий|История',
                'scenario_outline' => 'Структура сценария|Аутлайн',
                'examples' => 'Примеры',
                'given' => 'Допустим',
                'when' => 'Если|@',
                'then' => 'То',
                'and' => 'И',
                'but' => 'Но',
            ],
        ]);
    }

    public function testEnKeywordsDumper(): void
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('en');
        $etalon = <<<'GHERKIN'
        Feature: Internal operations
          In order to stay secret
          As a secret organization
          We need to be able to erase past agents' memory

          Background:
            Given there is agent A
            And there is agent B

          Scenario: Erasing agent memory
            Given there is agent J
            And there is agent K
            When I erase agent K's memory
            Then there should be agent J
            But there should not be agent K

          (Scenario Outline|Scenario Template): Erasing other agents' memory
            Given there is agent <agent1>
            And there is agent <agent2>
            When I erase agent <agent2>'s memory
            Then there should be agent <agent1>
            But there should not be agent <agent2>

            (Examples|Scenarios):
              | agent1 | agent2 |
              | D      | M      |
        GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }

    public function testRuKeywordsDumper(): void
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('ru');
        $etalon = <<<'GHERKIN'
        # language: ru
        (Функционал|Фича): Internal operations
          In order to stay secret
          As a secret organization
          We need to be able to erase past agents' memory

          (Предыстория|Бэкграунд):
            Допустим there is agent A
            И there is agent B

          (Сценарий|История): Erasing agent memory
            Допустим there is agent J
            И there is agent K
            (Если|@) I erase agent K's memory
            То there should be agent J
            Но there should not be agent K

          (Структура сценария|Аутлайн): Erasing other agents' memory
            Допустим there is agent <agent1>
            И there is agent <agent2>
            (Если|@) I erase agent <agent2>'s memory
            То there should be agent <agent1>
            Но there should not be agent <agent2>

            Примеры:
              | agent1 | agent2 |
              | D      | M      |
        GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }

    public function testRuKeywordsCustomKeywordsDumper(): void
    {
        $dumper = new KeywordsDumper($this->keywords);
        $dumper->setKeywordsDumperFunction(function ($keywords) {
            return '<keyword>' . implode(', ', $keywords) . '</keyword>';
        });

        $dumped = $dumper->dump('ru');
        $etalon = <<<'GHERKIN'
        # language: ru
        <keyword>Функционал, Фича</keyword>: Internal operations
          In order to stay secret
          As a secret organization
          We need to be able to erase past agents' memory

          <keyword>Предыстория, Бэкграунд</keyword>:
            <keyword>Допустим</keyword> there is agent A
            <keyword>И</keyword> there is agent B

          <keyword>Сценарий, История</keyword>: Erasing agent memory
            <keyword>Допустим</keyword> there is agent J
            <keyword>И</keyword> there is agent K
            <keyword>Если, @</keyword> I erase agent K's memory
            <keyword>То</keyword> there should be agent J
            <keyword>Но</keyword> there should not be agent K

          <keyword>Структура сценария, Аутлайн</keyword>: Erasing other agents' memory
            <keyword>Допустим</keyword> there is agent <agent1>
            <keyword>И</keyword> there is agent <agent2>
            <keyword>Если, @</keyword> I erase agent <agent2>'s memory
            <keyword>То</keyword> there should be agent <agent1>
            <keyword>Но</keyword> there should not be agent <agent2>

            <keyword>Примеры</keyword>:
              | agent1 | agent2 |
              | D      | M      |
        GHERKIN;

        $this->assertEquals($etalon, $dumped);
    }

    public function testExtendedVersionDumper(): void
    {
        $dumper = new KeywordsDumper($this->keywords);

        $dumped = $dumper->dump('ru', false);
        $etalon = [
            <<<'GHERKIN'
            # language: ru
            Функционал: Internal operations
              In order to stay secret
              As a secret organization
              We need to be able to erase past agents' memory

              Предыстория:
                Допустим there is agent A
                И there is agent B

              Сценарий: Erasing agent memory
                Допустим there is agent J
                И there is agent K
                Если I erase agent K's memory
                @ I erase agent K's memory
                То there should be agent J
                Но there should not be agent K

              История: Erasing agent memory
                Допустим there is agent J
                И there is agent K
                Если I erase agent K's memory
                @ I erase agent K's memory
                То there should be agent J
                Но there should not be agent K

              Структура сценария: Erasing other agents' memory
                Допустим there is agent <agent1>
                И there is agent <agent2>
                Если I erase agent <agent2>'s memory
                @ I erase agent <agent2>'s memory
                То there should be agent <agent1>
                Но there should not be agent <agent2>

                Примеры:
                  | agent1 | agent2 |
                  | D      | M      |

              Аутлайн: Erasing other agents' memory
                Допустим there is agent <agent1>
                И there is agent <agent2>
                Если I erase agent <agent2>'s memory
                @ I erase agent <agent2>'s memory
                То there should be agent <agent1>
                Но there should not be agent <agent2>

                Примеры:
                  | agent1 | agent2 |
                  | D      | M      |
            GHERKIN
            , <<<'GHERKIN'
            # language: ru
            Фича: Internal operations
              In order to stay secret
              As a secret organization
              We need to be able to erase past agents' memory

              Предыстория:
                Допустим there is agent A
                И there is agent B

              Сценарий: Erasing agent memory
                Допустим there is agent J
                И there is agent K
                Если I erase agent K's memory
                @ I erase agent K's memory
                То there should be agent J
                Но there should not be agent K

              История: Erasing agent memory
                Допустим there is agent J
                И there is agent K
                Если I erase agent K's memory
                @ I erase agent K's memory
                То there should be agent J
                Но there should not be agent K

              Структура сценария: Erasing other agents' memory
                Допустим there is agent <agent1>
                И there is agent <agent2>
                Если I erase agent <agent2>'s memory
                @ I erase agent <agent2>'s memory
                То there should be agent <agent1>
                Но there should not be agent <agent2>

                Примеры:
                  | agent1 | agent2 |
                  | D      | M      |

              Аутлайн: Erasing other agents' memory
                Допустим there is agent <agent1>
                И there is agent <agent2>
                Если I erase agent <agent2>'s memory
                @ I erase agent <agent2>'s memory
                То there should be agent <agent1>
                Но there should not be agent <agent2>

                Примеры:
                  | agent1 | agent2 |
                  | D      | M      |
            GHERKIN,
        ];

        $this->assertEquals($etalon, $dumped);
    }
}
