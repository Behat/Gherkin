<?php

namespace Tests\Behat\Gherkin;

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Lexer,
    Behat\Gherkin\Parser,
    Behat\Gherkin\Keywords\ArrayKeywords,
    Behat\Gherkin\Loader\YamlFileLoader;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    private $gherkin;
    private $yaml;

    protected function getGherkinParser()
    {
        if (null === $this->gherkin) {
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
        }

        return $this->gherkin;
    }

    protected function getYamlParser()
    {
        if (null === $this->yaml) {
            $this->yaml = new YamlFileLoader();
        }

        return $this->yaml;
    }

    protected function parseFixture($fixture)
    {
        return $this->getGherkinParser()->parse(__DIR__ . '/Fixtures/features/' . $fixture);
    }

    protected function parseEtalon($etalon)
    {
        $features = $this->getYamlParser()->load(__DIR__ . '/Fixtures/etalons/' . $etalon);
        $feature  = $features[0];
        $feature->setFile(__DIR__ . '/Fixtures/features/' . basename($etalon, '.yml') . '.feature');

        return $feature;
    }

    public function parserTestDataProvider()
    {
        $data = array();

        $finder = new Finder();
        $files  = $finder->files()->name('*.yml')->in(__DIR__ . '/Fixtures/etalons');

        foreach ($files as $file) {
            $testname = basename($file, '.yml');

            $etalonFeature      = $this->parseEtalon($testname . '.yml');
            $fixtureFeatures    = $this->parseFixture($testname . '.feature');

            $data[] = array($testname, $etalonFeature, $fixtureFeatures);
        }

        return $data;
    }

    /**
     * @dataProvider parserTestDataProvider
     */
    public function testParser($fixtureName, $etalon, $features)
    {
        $this->assertType('array', $features);
        $this->assertEquals(1, count($features));
        $fixture = $features[0];

        $this->assertEquals($etalon, $fixture);
    }
}
