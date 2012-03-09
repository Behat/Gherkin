<?php

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Node\FeatureNode;

/**
 * Features filecache.
 */
class FileCache implements CacheInterface
{
    private $path;

    /**
     * Initializes file cache.
     *
     * @param string $path path to the folder where to store caches.
     */
    public function __construct($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $this->path = rtrim($path, '/');
    }

    /**
     * Checks that cache for feature exists and is fresh.
     *
     * @param  string  $path      feature path
     * @param  integer $timestamp the last time feature was updated
     *
     * @return Boolean
     */
    public function isFresh($path, $timestamp)
    {
        $cachePath = $this->getCachePathFor($path);

        if (!file_exists($cachePath)) {
            return false;
        }

        return filemtime($cachePath) > $timestamp;
    }

    /**
     * Reads feature cache from path.
     *
     * @param   string $path feature path
     *
     * @return  FeatureNode
     */
    public function read($path)
    {
        return unserialize(file_get_contents($this->getCachePathFor($path)));
    }

    /**
     * Caches feature node.
     *
     * @param string      $path    feature path
     * @param FeatureNode $feature feature instance
     */
    public function write($path, FeatureNode $feature)
    {
        file_put_contents($this->getCachePathFor($path), serialize($feature));
    }

    /**
     * Returns feature cache file path from features path.
     *
     * @param  string $path feature path
     *
     * @return string
     */
    protected function getCachePathFor($path)
    {
        return $this->path.'/'.md5($path).'.feature.cache';
    }
}
