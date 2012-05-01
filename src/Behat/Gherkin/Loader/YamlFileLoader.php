<?php

namespace Behat\Gherkin\Loader;

use Symfony\Component\Yaml\Yaml;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Yaml files loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class YamlFileLoader extends ArrayLoader implements FileLoaderInterface
{
    protected $basePath;

    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return Boolean
     */
    public function supports($path)
    {
        return is_string($path)
            && is_file($absolute = $this->findAbsolutePath($path))
            && 'yml' === pathinfo($absolute, PATHINFO_EXTENSION);
    }

    /**
     * Loads features from provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return array
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);
        $hash = Yaml::parse($path);

        $features = parent::load($hash);
        $filename = $this->findRelativePath($path);

        foreach ($features as $feature) {
            $feature->setFile($filename);
        }

        return $features;
    }

    /**
     * Sets base features path.
     *
     * @param string $path Base loader path
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
        if (null !== $this->basePath) {
            return strtr($path, array($this->basePath . DIRECTORY_SEPARATOR => ''));
        }

        return $path;
    }

    /**
     * Finds absolute path for provided relative (relative to base features path).
     *
     * @param string $path Relative path
     *
     * @return string
     */
    protected function findAbsolutePath($path)
    {
        if (is_file($path) || is_dir($path)) {
            return realpath($path);
        } elseif (is_file($this->basePath . DIRECTORY_SEPARATOR . $path)
               || is_dir($this->basePath . DIRECTORY_SEPARATOR . $path)) {
            return realpath($this->basePath . DIRECTORY_SEPARATOR . $path);
        }

        return false;
    }
}
