<?php

namespace Behat\Gherkin\Cache;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Gherkin\Node\FeatureNode;
use Doctrine\Common\Cache\Cache;

/**
 * Doctrine base cache provider.
 *
 * Caches feature into cache system provided by Doctrine, i.e.:
 * Apc, Memcache(d), XCache, WinCache and others.
 *
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
class DoctrineCache implements CacheInterface
{
    private $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($path, $timestamp)
    {
        if (!$this->cache->contains($path.'_timestamp')) {
            return false;
        }

        return $this->cache->fetch($path.'_timestamp') > $timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->cache->fetch($path);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, FeatureNode $feature)
    {
        $this->cache->save($path, $feature);
        $this->cache->save($path.'_timestamp', time());
    }
}
