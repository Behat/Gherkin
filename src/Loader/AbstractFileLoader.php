<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

/**
 * Abstract filesystem loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class AbstractFileLoader implements FileLoaderInterface
{
    /**
     * @var string|null
     */
    protected $basePath;

    /**
     * Sets base features path.
     *
     * @param string $path Base loader path
     *
     * @return void
     */
    public function setBasePath($path)
    {
        $this->basePath = realpath($path);
    }

    /**
     * Finds relative path for provided absolute (relative to base features path).
     *
     * @param string $path Absolute path
     *
     * @return string
     */
    protected function findRelativePath($path)
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
    protected function findAbsolutePath($path)
    {
        if (is_file($path) || is_dir($path)) {
            return realpath($path);
        }

        if ($this->basePath === null) {
            return false;
        }

        if (is_file($this->basePath . DIRECTORY_SEPARATOR . $path)
               || is_dir($this->basePath . DIRECTORY_SEPARATOR . $path)) {
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
