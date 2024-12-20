<?php

namespace Tests\Behat\Gherkin\Cache;

use Behat\Gherkin\Cache\FileCache;
use Behat\Gherkin\Exception\CacheException;
use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private $path;
    private $cache;

    public function testIsFreshWhenThereIsNoFile(): void
    {
        $this->assertFalse($this->cache->isFresh('unexisting', time() + 100));
    }

    public function testIsFreshOnFreshFile(): void
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertFalse($this->cache->isFresh('some_path', time() + 100));
    }

    public function testIsFreshOnOutdated(): void
    {
        $feature = new FeatureNode(null, null, array(), null, array(), null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertTrue($this->cache->isFresh('some_path', time() - 100));
    }

    public function testCacheAndRead(): void
    {
        $scenarios = array(new ScenarioNode('Some scenario', array(), array(), null, null));
        $feature = new FeatureNode('Some feature', 'some description', array(), null, $scenarios, null, null, null, null);

        $this->cache->write('some_feature', $feature);
        $featureRead = $this->cache->read('some_feature');

        $this->assertEquals($feature, $featureRead);
    }

    public function testBrokenCacheRead(): void
    {
        $this->expectException(CacheException::class);

        touch($this->path . '/v' . Gherkin::VERSION . '/' . md5('broken_feature') . '.feature.cache');
        $this->cache->read('broken_feature');
    }

    public function testUnwriteableCacheDir(): void
    {
        $this->expectException(CacheException::class);

        if (PHP_OS_FAMILY === 'Windows') {
            new FileCache('C:\\Windows\\System32\\drivers\\etc');
        } else {
            new FileCache('/dev/null/gherkin-test');
        }
    }

    protected function setUp(): void
    {
        $this->cache = new FileCache($this->path = sys_get_temp_dir() . '/gherkin-test');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->path . '/*.feature.cache') as $file) {
            unlink((string) $file);
        }
    }
}
