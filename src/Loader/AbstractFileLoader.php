<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Filesystem;

/**
 * Abstract filesystem loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @template TResourceType
 *
 * @extends AbstractLoader<TResourceType>
 *
 * @implements FileLoaderInterface<TResourceType>
 */
abstract class AbstractFileLoader extends AbstractLoader implements FileLoaderInterface
{
    /**
     * @var string|null
     */
    protected $basePath;

    /**
     * Sets base features path.
     *
     * @return void
     */
    public function setBasePath(string $path)
    {
        $this->basePath = Filesystem::getRealPath($path);
    }

    /**
     * Finds relative path for provided absolute (relative to base features path).
     *
     * @param string $path Absolute path
     *
     * @return string
     */
    protected function findRelativePath(string $path)
    {
        if ($this->basePath !== null) {
            return strtr($path, [$this->basePath . DIRECTORY_SEPARATOR => '']);
        }

        return $path;
    }

    /**
     * Finds absolute path for provided relative (relative to base features path).
     *
     * @param string $path Relative path
     *
     * @return false|string
     */
    protected function findAbsolutePath(string $path)
    {
        if (file_exists($path)) {
            return realpath($path);
        }

        if ($this->basePath === null) {
            return false;
        }

        if (file_exists($this->basePath . DIRECTORY_SEPARATOR . $path)) {
            return realpath($this->basePath . DIRECTORY_SEPARATOR . $path);
        }

        return false;
    }

    /**
     * @throws \RuntimeException
     */
    final protected function getAbsolutePath(string $path): string
    {
        $resolvedPath = $this->findAbsolutePath($path);
        if ($resolvedPath === false) {
            throw new \RuntimeException("Unable to locate absolute path of \"$path\"");
        }

        return $resolvedPath;
    }
}
