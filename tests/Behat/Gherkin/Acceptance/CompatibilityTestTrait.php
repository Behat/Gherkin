<?php

namespace Tests\Behat\Gherkin\Acceptance;

use Behat\Gherkin\Loader\YamlFileLoader;
use Behat\Gherkin\Node\FeatureNode;

trait CompatibilityTestTrait
{
    private $yaml;

    abstract protected function parseFeature($featureFile) : FeatureNode;

    /**
     * @dataProvider etalonsProvider
     */
    public function testItParsesTheBehatEtalons($yamlFile, $featureFile)
    {
        $fixture = $this->getYamlParser()->load($yamlFile)[0];
        $feature = $this->parseFeature($featureFile);

        $this->compare($fixture, $feature);
    }

    public function etalonsProvider()
    {
        foreach (glob(__DIR__ . '/../Fixtures/etalons/*.yml') as $file) {
            $testname = basename($file, '.yml');

            if (!in_array($testname, $this->etalons_skip)) {
                yield $testname => [$file, __DIR__ . '/../Fixtures/features/'.$testname.'.feature'];
            }

        }
    }

    protected function getYamlParser()
    {
        if (null === $this->yaml) {
            $this->yaml = new YamlFileLoader();
        }

        return $this->yaml;
    }

    private function compare(FeatureNode $fixture, ?FeatureNode $feature): void
    {
        $rc = new \ReflectionClass(FeatureNode::class);
        $rp = $rc->getProperty('file');
        $rp->setAccessible(true);
        $rp->setValue($fixture, null);
        $rp->setValue($feature, null);

        $this->assertEquals($fixture, $feature);
    }

}
