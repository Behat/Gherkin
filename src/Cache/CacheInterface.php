<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Node\FeatureNode;

/**
 * Parser cache interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface CacheInterface
{
    /**
     * Checks that cache for feature exists and is fresh.
     *
     * @param string $path Feature path
     * @param int $timestamp The last time feature was updated
     *
     * @return bool
     */
    public function isFresh(string $path, int $timestamp);

    /**
     * Reads feature cache from path.
     *
     * @param string $path Feature path
     *
     * @return FeatureNode
     */
    public function read(string $path);

    /**
     * Caches feature node.
     *
     * @param string $path Feature path
     *
     * @return void
     */
    public function write(string $path, FeatureNode $feature);
}
