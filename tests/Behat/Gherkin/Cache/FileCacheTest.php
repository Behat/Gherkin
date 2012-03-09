<?php

namespace Tests\Behat\Gherkin\Cache;

use Behat\Gherkin\Cache\FileCache,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\FeatureNode;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    private $path;
    private $cache;

    protected function setUp()
    {
        $this->cache = new FileCache($this->path = sys_get_temp_dir().'/gherkin-test');
    }

    protected function tearDown()
    {
        foreach (glob($this->path.'/*.feature.cache') as $file) {
            unlink((string) $file);
        }
    }

    public function testIsFreshWhenThereIsNoFile()
    {
        $this->assertFalse($this->cache->isFresh('unexisting', time() + 100));
    }

    public function testIsFreshOnFreshFile()
    {
        $feature = new FeatureNode();

        $this->cache->write('some_path', $feature);

        $this->assertFalse($this->cache->isFresh('some_path', time() + 100));
    }

    public function testIsFreshOnOutdated()
    {
        $feature = new FeatureNode();

        $this->cache->write('some_path', $feature);

        $this->assertTrue($this->cache->isFresh('some_path', time() - 100));
    }

    public function testCacheAndRead()
    {
        $feature = new FeatureNode('Some feature', 'some description');
        $feature->addScenario(new ScenarioNode('Some scenario'));

        $this->cache->write('some_feature', $feature);
        $featureRead = $this->cache->read('some_feature');

        $this->assertEquals($feature, $featureRead);
    }
}
