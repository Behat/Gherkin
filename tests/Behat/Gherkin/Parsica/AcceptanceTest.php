<?php
declare(strict_types=1);

namespace Behat\Gherkin\Parsica;

use Behat\Gherkin\Loader\YamlFileLoader;
use PHPUnit\Framework\TestCase;
use Verraes\Parsica\PHPUnit\ParserAssertions;

/** @group parsica-acceptance */
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
        
        $this->assertSame($expected, $actual);
    }
    
    static function gherkinFiles()
    {
        $files = glob(__DIR__ . '/../Fixtures/features/*.feature');

        return array_map(
            fn ($file) => [
                $file,
                preg_replace('#features/(.*)\.feature#', 'etalons/\1.yml', $file)
            ],
            $files
        );
    }
    
    private static function loadFromEtalon($etalonFile)
    {
        static $yamlFileLoader;
        
        $yamlFileLoader = $yamlFileLoader ? $yamlFileLoader : new YamlFileLoader();
        
        return $yamlFileLoader->load($etalonFile);
    }
    
    private static function parseFeature($feature)
    {
        return gherkin()->tryString($feature)->output();
    }
}
