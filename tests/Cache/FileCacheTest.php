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
use Behat\Gherkin\Filesystem;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileCacheTest extends TestCase
{
    public function testIsFreshWhenThereIsNoFile(): void
    {
        $cache = $this->createCache();

        $this->assertFalse($cache->isFresh('unexisting', time() + 100));
    }

    public function testIsFreshOnFreshFile(): void
    {
        $cache = $this->createCache();
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $cache->write('some_path', $feature);

        $this->assertFalse($cache->isFresh('some_path', time() + 100));
    }

    public function testIsFreshOnOutdated(): void
    {
        $cache = $this->createCache();
        $feature = new FeatureNode(null, null, [], null, [], '', '', null, 1);

        $cache->write('some_path', $feature);

        $this->assertTrue($cache->isFresh('some_path', time() - 100));
    }

    public function testCacheAndRead(): void
    {
        $cache = $this->createCache();
        $scenarios = [new ScenarioNode('Some scenario', [], [], '', 1)];
        $feature = new FeatureNode('Some feature', 'some description', [], null, $scenarios, '', '', null, 1);

        $cache->write('some_feature', $feature);
        $featureRead = $cache->read('some_feature');

        $this->assertEquals($feature, $featureRead);
    }

    public function testBrokenCacheRead(): void
    {
        $root = $this->createRoot();
        $cache = $this->createCache($root);
        // First, write a valid cache and find the file that was written
        $cache->write(
            'broken_feature',
            new FeatureNode(null, null, [], null, [], '', '', null, 1),
        );
        $files = Filesystem::findFilesRecursively($root->url(), '*.feature.cache');

        $this->assertCount(1, $files, 'Cache should have written a single file');

        // Now simulate the file being corrupted and attempt to read it
        file_put_contents($files[0], '');

        $this->expectException(CacheException::class);
        $this->expectExceptionMessageMatches('/^Can not load cache for a feature "broken_feature" from .+$/');

        $cache->read('broken_feature');
    }

    public function testMissingCacheFileRead(): void
    {
        $cache = $this->createCache();

        $this->expectException(CacheException::class);
        $this->expectExceptionMessageMatches('/^Can not load cache: File ".+" cannot be read: .+$/');

        $cache->read('missing_file');
    }

    public function testUnwritableCachePath(): void
    {
        $root = $this->createRoot();
        mkdir($root->url() . '/' . $this->getCacheDirName($root), 0);
        clearstatcache();

        $this->expectExceptionMessageMatches('/^Cache path ".+" is not writeable\. Check your filesystem permissions or disable Gherkin file cache\.$/');
        $this->expectException(CacheException::class);

        new FileCache($root->url());
    }

    public function testNonDirectoryCachePath(): void
    {
        $root = $this->createRoot();
        touch($root->url() . '/' . $this->getCacheDirName($root));
        clearstatcache();

        $this->expectExceptionMessageMatches('/^Cache path ".+" cannot be created or is not a directory: Path at .+ cannot be created: mkdir\(\): Path .+ exists$/');
        $this->expectException(CacheException::class);

        new FileCache($root->url());
    }

    private function createRoot(): vfsStreamDirectory
    {
        return vfsStream::setup();
    }

    private function createCache(?vfsStreamDirectory $root = null): FileCache
    {
        return new FileCache(
            ($root ?? $this->createRoot())->url()
        );
    }

    private function getCacheDirName(vfsStreamDirectory $root): string
    {
        $this->createCache($root);

        $cacheDirName = ($root->getChildren()[0] ?: throw new RuntimeException('Cache directory not created'))->getName();
        $root->removeChild($cacheDirName);

        return $cacheDirName;
    }
}
