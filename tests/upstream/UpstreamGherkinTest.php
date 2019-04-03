<?php

use Behat\Gherkin\Keywords\ArrayKeywords;

final class UpstreamGherkinTest extends PHPUnit_Framework_TestCase
{

    private $notSupported = array(
        'good' => array(
            'descriptions',
            'docstrings',
            'incomplete_feature_3',
            'incomplete_scenario_outline',
            'readme_example',
            'rule',
            'scenario_outline',
            'scenario_outlines_with_tags',
            'several_examples',
            'spaces_in_language',
            'tags'
        )
    );

    /**
     * @dataProvider goodFeatures
     */
    public function testGoodFeatures($featureFile, $astFile)
    {
        $arrKeywords = include __DIR__ . '/../../i18n.php';
        $lexer  = new Behat\Gherkin\Lexer(new ArrayKeywords($arrKeywords));
        $parser = new Behat\Gherkin\Parser($lexer);

        $feature = $parser->parse(file_get_contents($featureFile));
    }

    public function goodFeatures() : iterable
    {
       foreach (new FilesystemIterator(__DIR__ . '/../../gherkin_testdata/good') as $file) {

           if (in_array(preg_replace('/^.*\//', '', $file), $this->notSupported['good'])) {
               continue;
           }

           if (!preg_match('/(?<stem>.*)[.]feature$/', $file, $matches)) {
               continue;
           }

           if (in_array(preg_replace('/^.*\//', '', $matches['stem']), $this->notSupported['good'])) {
               continue;
           }

           yield $matches['stem'] => [
               $matches['stem'] . '.feature',
               $matches['stem'] . '.ast.ndjson'
           ];
       }
    }
}
