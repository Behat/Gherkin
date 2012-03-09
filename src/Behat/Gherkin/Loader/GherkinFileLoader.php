<?php

namespace Behat\Gherkin\Loader;

use Symfony\Component\Finder\Finder;

use Behat\Gherkin\Parser,
    Behat\Gherkin\Cache\CacheInterface;

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
class GherkinFileLoader extends AbstractFileLoader
{
    protected $parser;
    protected $cache;

    /**
     * Initializes loader.
     *
     * @param   Behat\Gherkin\Parser                $parser
     * @param   Behat\Gherkin\Cache\CacheInterface  $cache
     */
    public function __construct(Parser $parser, CacheInterface $cache = null)
    {
        $this->parser = $parser;
        $this->cache  = $cache;
    }

    /**
     * Sets loader cache.
     *
     * @param   Behat\Gherkin\Cache\CacheInterface $cache cache instance
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($path)
    {
        return is_string($path)
            && is_file($absolute = $this->findAbsolutePath($path))
            && 'feature' === pathinfo($absolute, PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    public function load($path)
    {
        $path = $this->findAbsolutePath($path);

        if ($this->cache) {
             if ($this->cache->isFresh($path, filemtime($path))) {
                 $feature = $this->cache->read($path);
             } else {
                 $feature = $this->parseFeature($path);
                 $this->cache->write($path, $feature);
             }
        } else {
            $feature = $this->parseFeature($path);
        }

        return array($feature);
    }

    /**
     * Parses feature at provided absolute path.
     *
     * @param  string $path
     *
     * @return FeatureNode
     */
    protected function parseFeature($path)
    {
        $filename = $this->findRelativePath($path);
        $content  = file_get_contents($path);

        return $this->parser->parse($content, $filename);
    }
}
