<?php

namespace Behat\Gherkin\Loader;

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Parser;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin *.feature files loader.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GherkinFileLoader implements LoaderInterface
{
    protected $parser;
    protected $basePath;

    /**
     * Initialize loader.
     *
     * @param   Parser  $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Set base features path.
     *
     * @param   string  $path
     */
    public function setBasePath($path)
    {
        $this->basePath = realpath($path);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($path)
    {
        return is_file($path)
            || is_dir($path)
            || is_file($this->basePath . '/' . $path)
            || is_dir($this->basePath . '/' . $path);
    }

    /**
     * {@inheritdoc}
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);

        if (is_dir($path)) {
            $finder     = new Finder();
            $iterator   = $finder->files()->name('*.feature')->in($path);
            $features   = array();

            foreach ($iterator as $path) {
                $filename   = $this->findRelativePath($path);
                $content    = file_get_contents($path);
                $features   = array_merge($features, $this->parser->parse($content, $filename));
            }

            return $features;
        } else {
            $filename   = $this->findRelativePath($path);
            $content    = file_get_contents($path);

            return $this->parser->parse($content, $filename);
        }
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

        throw new \InvalidArgumentException('Feature path not found: ' . $path);
    }
}
