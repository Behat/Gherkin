<?php

namespace Behat\Gherkin\Loader;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Abstract filesystem loader.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class AbstractFileLoader implements FileLoaderInterface
{
    protected $basePath;

    /**
     * {@inheritdoc}
     */
    public function setBasePath($path)
    {
        $this->basePath = realpath($path);
    }

    /**
     * Find relative path for provided absolute (relative to base features path).
     *
     * @param   string  $path   absolute path
     * 
     * @return  string
     */
    protected function findRelativePath($path)
    {
        if (null !== $this->basePath) {
            return strtr($path, array($this->basePath . '/' => ''));
        }

        return $path;
    }

    /**
     * Find absolute path for provided relative (relative to base features path).
     *
     * @param   string  $path   relative path
     * 
     * @return  string
     */
    protected function findAbsolutePath($path)
    {
        if (is_file($path) || is_dir($path)) {
            return realpath($path);
        } elseif (is_file($this->basePath . '/' . $path) || is_dir($this->basePath . '/' . $path)) {
            return realpath($this->basePath . '/' . $path);
        }

        return false;
    }
}
