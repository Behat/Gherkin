<?php

namespace Tests\Behat\Gherkin\Acceptance;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;

final class LegacyParserTest extends TestCase
{
    private $gherkin;
    protected $etalons_skip = [];

    use CompatibilityTestTrait;

    protected function parseFeature($featureFile) : FeatureNode
    {
        return $this->getGherkinParser()->parse(file_get_contents($featureFile), $featureFile);
    }

    protected function getGherkinParser()
    {
        if (null === $this->gherkin) {
            $keywords       = new ArrayKeywords(array(
                'en' => array(
                    'feature'          => 'Feature',
                    'background'       => 'Background',
                    'scenario'         => 'Scenario',
                    'scenario_outline' => 'Scenario Outline',
                    'examples'         => 'Examples',
                    'given'            => 'Given',
                    'when'             => 'When',
                    'then'             => 'Then',
                    'and'              => 'And',
                    'but'              => 'But'
                ),
                'ru' => array(
                    'feature'          => 'Функционал',
                    'background'       => 'Предыстория',
                    'scenario'         => 'Сценарий',
                    'scenario_outline' => 'Структура сценария',
                    'examples'         => 'Примеры',
                    'given'            => 'Допустим',
                    'when'             => 'Если',
                    'then'             => 'То',
                    'and'              => 'И',
                    'but'              => 'Но'
                ),
                'ja' => array (
                    'feature'           => 'フィーチャ',
                    'background'        => '背景',
                    'scenario'          => 'シナリオ',
                    'scenario_outline'  => 'シナリオアウトライン',
                    'examples'          => '例|サンプル',
                    'given'             => '前提<',
                    'when'              => 'もし<',
                    'then'              => 'ならば<',
                    'and'               => 'かつ<',
                    'but'               => 'しかし<'
                )
            ));
            $this->gherkin  = new Parser(new Lexer($keywords));
        }

        return $this->gherkin;
    }
}
