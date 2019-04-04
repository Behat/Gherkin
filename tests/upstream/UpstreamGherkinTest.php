<?php

namespace Behat\Gherkin;

final class UpstreamGherkinTest extends \PHPUnit_Framework_TestCase
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
        ),
        'bad' => array(
            'invalid_language'
        )
    );

    /**
     * @var \Behat\Gherkin\Parser
     */
    private $parser;

    public function setUp()
    {
        $arrKeywords = include __DIR__ . '/../../i18n.php';
        $lexer  = new Lexer(new Keywords\ArrayKeywords($arrKeywords));
        $this->parser = new Parser($lexer);
    }

    /**
     * @dataProvider goodFeatures
     */
    public function testGoodFeaturesCanParse($featureFile)
    {
        $this->parser->parse(file_get_contents($featureFile));
    }

    /**
     * @dataProvider badFeatures
     */
    public function testBadFeaturesCanNotParse($featureFile)
    {
        $this->expectException(\Exception::class);
        $this->parser->parse(file_get_contents($featureFile));
    }

    public function goodFeatures()
    {
        return $this->findFeatures('good');
    }

    public function badFeatures()
    {
        return $this->findFeatures('bad');
    }

    /**
     * @param $type
     * @return array
     */
    private function findFeatures($type)
    {
        $features = array();

        foreach (new \FilesystemIterator(__DIR__ . '/../../gherkin_testdata/' . $type) as $file) {
            if (in_array(preg_replace('/^.*\//', '', $file), $this->notSupported[$type])) {
                continue;
            }

            if (!preg_match('/(?<stem>.*)[.]feature$/', $file, $matches)) {
                continue;
            }

            if (in_array(preg_replace('/^.*\//', '', $matches['stem']), $this->notSupported[$type])) {
                continue;
            }

            $features[$matches['stem']] = array(
                $matches['stem'] . '.feature'
            );
        }

        return $features;
    }
}
