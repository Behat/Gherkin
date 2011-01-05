<?php

namespace Tests\Behat\Gherkin;

require_once 'Fixtures/YamlParser.php';

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Keywords\ArrayKeywords;

use Tests\Behat\Gherkin\Fixtures\YamlParser;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    private $gherkin;
    private $yaml;

    protected function setUp()
    {
        $keywords       = new ArrayKeywords(array(
            'en' => array(
                'Feature'           => 'Feature',
                'Background'        => 'Background',
                'Scenario'          => 'Scenario',
                'Scenario Outline'  => 'Scenario Outline',
                'Examples'          => 'Examples',
                'Step Types'        => 'Given|When|Then|And|But'
            ),
            'ru' => array(
                'Feature'           => 'Функционал',
                'Background'        => 'Предыстория',
                'Scenario'          => 'Сценарий',
                'Scenario Outline'  => 'Структура сценария',
                'Examples'          => 'Значения',
                'Step Types'        => 'Допустим|То|Если|И|Но'
            )
        ));
        $this->gherkin  = new Parser(new Lexer($keywords));
        $this->yaml     = new YamlParser();
    }

    protected function parseFixture($fixture)
    {
        return $this->gherkin->parse(__DIR__ . '/Fixtures/features/' . $fixture);
    }

    protected function parseEtalon($etalon)
    {
        return $this->yaml->parse(
            __DIR__ . '/Fixtures/etalons/' . $etalon,
            __DIR__ . '/Fixtures/features/' . basename($etalon, '.yml') . '.feature'
        );
    }

    public function testFixtures()
    {
        $finder = new Finder();
        $files  = $finder->files()->name('*.yml')->in(__DIR__ . '/Fixtures/etalons');

        foreach ($files as $file) {
            $testname = basename($file, '.yml');

            $etalonFeature = $this->parseEtalon($testname . '.yml');

            $features = $this->parseFixture($testname . '.feature');
            $this->assertType('array', $features);
            $this->assertEquals(1, count($features));
            $fixtureFeature = $features[0];

            $this->assertEquals(
                $etalonFeature, $fixtureFeature, sprintf('Testing "%s"', $testname)
            );
        }
    }
}
