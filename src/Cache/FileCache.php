<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Cache;

use Behat\Gherkin\Exception\CacheException;
use Behat\Gherkin\Exception\FilesystemException;
use Behat\Gherkin\Filesystem;
use Behat\Gherkin\Node\FeatureNode;
use Composer\InstalledVersions;

/**
 * File cache.
 * Caches feature into a file.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class FileCache implements CacheInterface
{
    private readonly string $path;

    /**
     * Used as part of the cache directory path to invalidate cache if the installed package version changes.
     */
    private static function getGherkinVersionHash(): string
    {
        $version = InstalledVersions::getVersion('behat/gherkin') ?? 'unknown';

        // Composer version strings can contain arbitrary content so hash for filesystem safety
        return md5($version);
    }

    /**
     * Initializes file cache.
     *
     * @param string $path path to the folder where to store caches
     *
     * @throws CacheException
     */
    public function __construct(string $path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::getGherkinVersionHash();

        try {
            Filesystem::ensureDirectoryExists($this->path);
        } catch (FilesystemException $ex) {
            throw new CacheException(
                sprintf(
                    'Cache path "%s" cannot be created or is not a directory: %s',
                    $this->path,
                    $ex->getMessage(),
                ),
                previous: $ex
            );
        }

        if (!is_writable($this->path)) {
            throw new CacheException(sprintf('Cache path "%s" is not writeable. Check your filesystem permissions or disable Gherkin file cache.', $this->path));
        }
    }

    /**
     * Checks that cache for feature exists and is fresh.
     *
     * @param string $path Feature path
     * @param int $timestamp The last time feature was updated
     *
     * @return bool
     */
    public function isFresh(string $path, int $timestamp)
    {
        $cachePath = $this->getCachePathFor($path);

        if (!file_exists($cachePath)) {
            return false;
        }

        return Filesystem::getLastModified($cachePath) > $timestamp;
    }

    /**
     * Reads feature cache from path.
     *
     * @param string $path Feature path
     *
     * @return FeatureNode
     *
     * @throws CacheException
     */
    public function read(string $path)
    {
        $cachePath = $this->getCachePathFor($path);
        try {
            $feature = unserialize(Filesystem::readFile($cachePath), ['allowed_classes' => true]);
        } catch (FilesystemException $ex) {
            throw new CacheException("Can not load cache: {$ex->getMessage()}", previous: $ex);
        }

        if (!$feature instanceof FeatureNode) {
            throw new CacheException(sprintf('Can not load cache for a feature "%s" from "%s".', $path, $cachePath));
        }

        return $feature;
    }

    /**
     * Caches feature node.
     *
     * @param string $path Feature path
     *
     * @return void
     */
    public function write(string $path, FeatureNode $feature)
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
    protected function getCachePathFor(string $path)
    {
        return $this->path . '/' . md5($path) . '.feature.cache';
    }
}
