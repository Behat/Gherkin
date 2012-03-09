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
 * Parser cache interface.
 *
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface CacheInterface
{
    /**
     * Checks that cache for feature exists and is fresh.
     *
     * @param  string  $path      feature path
     * @param  integer $timestamp the last time feature was updated
     *
     * @return Boolean
     */
    function isFresh($path, $timestamp);

    /**
     * Reads feature cache from path.
     *
     * @param   string $path feature path
     *
     * @return  FeatureNode
     */
    function read($path);

    /**
     * Caches feature node.
     *
     * @param string      $path    feature path
     * @param FeatureNode $feature feature instance
     */
    function write($path, FeatureNode $feature);
}
