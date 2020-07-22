<?php
declare(strict_types=1);

namespace Behat\Gherkin\Parsica;

use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;

/** @group acceptance */
final class AcceptanceTest extends TestCase
{
    use ParserAssertions;
    
    /** 
     * @test 
     * @dataProvider gherkinFiles
     */
    function it_parses_the_same_as_older_parser($gherkinFile, $etalonFile)
    {
        $expected = $this->loadFromEtalon($etalonFile);
        
        $actual = $this->parseFeature(file_get_contents($gherkinFile));
        
        $this->assertEquals($expected, $actual);
    }
    
    static function gherkinFiles()
    {
        $files = glob(__DIR__ . '/../Fixtures/etalons/*.yml');

        foreach (array_map(
            fn ($file) => [
                preg_replace('#etalons/(.*)\.yml#', 'features/\1.feature', $file),
                $file
            ],
            $files
        ) as $tuple) {
            yield basename($tuple[0]) => $tuple;
        }
    }
    
    private static function loadFromEtalon($etalonFile)
    {
        static $yamlFileLoader;
        
        $yamlFileLoader = $yamlFileLoader ? $yamlFileLoader : new YamlFileLoader();

        $featureNodes = $yamlFileLoader->load($etalonFile);
        $feature = $featureNodes[0];

        $featureNode = new FeatureNode(
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getTags(),
            $feature->getBackground(),
            $feature->getScenarios(),
            $feature->getKeyword(),
            $feature->getLanguage(),
            null,
            $feature->getLine()
        );

        return $featureNode;
    }
    
    private static function parseFeature($feature)
    {
        return gherkin()->tryString($feature)->output();
    }
}
