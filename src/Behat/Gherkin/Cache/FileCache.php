<?php

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Node\FeatureNode;

/*
* This file is part of the Behat Gherkin.
* (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * File cache.
 * Caches feature into a file.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FileCache implements CacheInterface
{
    private $path;

    /**
     * Initializes file cache.
     *
     * @param string $path Path to the folder where to store caches.
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
     * @param string  $path      Feature path
     * @param integer $timestamp The last time feature was updated
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
     * @param string $path Feature path
     *
     * @return FeatureNode
     */
    public function read($path)
    {
        return unserialize(file_get_contents($this->getCachePathFor($path)));
    }

    /**
     * Caches feature node.
     *
     * @param string      $path    Feature path
     * @param FeatureNode $feature Feature instance
     */
    public function write($path, FeatureNode $feature)
    {
        file_put_contents($this->getCachePathFor($path), serialize($feature));
    }

    /**
     * Returns feature cache file path from features path.
     *
     * @param string $path Feature path
     *
     * @return string
     */
    protected function getCachePathFor($path)
    {
        return $this->path.'/'.md5($path).'.feature.cache';
    }
}
