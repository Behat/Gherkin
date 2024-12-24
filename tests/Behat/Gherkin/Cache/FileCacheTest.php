<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Cache;

use Behat\Gherkin\Cache\FileCache;
use Behat\Gherkin\Exception\CacheException;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

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
        $feature = new FeatureNode(null, null, [], null, [], null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertFalse($this->cache->isFresh('some_path', time() + 100));
    }

    public function testIsFreshOnOutdated(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], null, null, null, null);

        $this->cache->write('some_path', $feature);

        $this->assertTrue($this->cache->isFresh('some_path', time() - 100));
    }

    public function testCacheAndRead(): void
    {
        $scenarios = [new ScenarioNode('Some scenario', [], [], null, null)];
        $feature = new FeatureNode('Some feature', 'some description', [], null, $scenarios, null, null, null, null);

        $this->cache->write('some_feature', $feature);
        $featureRead = $this->cache->read('some_feature');

        $this->assertEquals($feature, $featureRead);
    }

    public function testBrokenCacheRead(): void
    {
        // First, write a valid cache and find the file that was written
        $this->cache->write(
            'broken_feature',
            new FeatureNode(null, null, [], null, [], null, null, null, null),
        );
        $files = glob($this->path . '/**/*.feature.cache');
        $this->assertCount(1, $files, 'Cache should have written a single file');

        // Now simulate the file being corrupted and attempt to read it
        file_put_contents($files[0], '');

        $this->expectException(CacheException::class);

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
        $this->cache = new FileCache($this->path = sys_get_temp_dir() . uniqid('/gherkin-test'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->path);
    }
}
