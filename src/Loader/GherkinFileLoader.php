<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Cache\CacheInterface;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;

/**
 * Gherkin *.feature files loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class GherkinFileLoader extends AbstractFileLoader
{
    /**
     * @var Parser
     */
    protected $parser;
    /**
     * @var CacheInterface|null
     */
    protected $cache;

    /**
     * Initializes loader.
     *
     * @param Parser $parser Parser
     * @param CacheInterface|null $cache Cache layer
     */
    public function __construct(Parser $parser, ?CacheInterface $cache = null)
    {
        $this->parser = $parser;
        $this->cache = $cache;
    }

    /**
     * Sets cache layer.
     *
     * @param CacheInterface $cache Cache layer
     *
     * @return void
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return bool
     */
    public function supports($resource)
    {
        return is_string($resource)
            && ($path = $this->findAbsolutePath($resource)) !== false
            && is_file($path)
            && pathinfo($path, PATHINFO_EXTENSION) === 'feature';
    }

    /**
     * Loads features from provided resource.
     *
     * @param string $resource Resource to load
     *
     * @return list<FeatureNode>
     */
    public function load($resource)
    {
        $path = $this->getAbsolutePath($resource);
        if ($this->cache) {
            if ($this->cache->isFresh($path, filemtime($path))) {
                $feature = $this->cache->read($path);
            } elseif (null !== $feature = $this->parseFeature($path)) {
                $this->cache->write($path, $feature);
            }
        } else {
            $feature = $this->parseFeature($path);
        }

        return $feature !== null ? [$feature] : [];
    }

    /**
     * Parses feature at provided absolute path.
     *
     * @param string $path Feature path
     *
     * @return FeatureNode|null
     */
    protected function parseFeature($path)
    {
        $content = file_get_contents($path);

        return $this->parser->parse($content, $path);
    }
}
