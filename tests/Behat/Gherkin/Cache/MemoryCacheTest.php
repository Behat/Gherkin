<?php

namespace Tests\Behat\Gherkin\Cache;

use Behat\Gherkin\Cache\MemoryCache;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;

class MemoryCacheTest extends TestCase
{
    private $cache;

    public function testIsFreshWhenThereIsNoFile()
    {
        $this->assertFalse($this->cache->isFresh('unexisting', time() + 100));
    }

    public function testIsFreshOnFreshFile()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), array(), null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertFalse($this->cache->isFresh('some_path', time() + 100));
    }

    public function testIsFreshOnOutdated()
    {
        $feature = new FeatureNode(null, null, array(), null, array(), array(), null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertTrue($this->cache->isFresh('some_path', time() - 100));
    }

    public function testCacheAndRead()
    {
        $scenarios = array(new ScenarioNode('Some scenario', array(), array(), null, null));
        $feature = new FeatureNode('Some feature', 'some description', array(), null, array(), $scenarios, null, null, null, null);

        $this->cache->write('some_feature', $feature);
        $featureRead = $this->cache->read('some_feature');

        $this->assertEquals($feature, $featureRead);
    }

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
    }
}
