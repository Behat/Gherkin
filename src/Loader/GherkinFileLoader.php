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
use Behat\Gherkin\Filesystem;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\ParserInterface;

/**
 * Gherkin *.feature files loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @extends AbstractFileLoader<string>
 */
class GherkinFileLoader extends AbstractFileLoader
{
    /**
     * @var ParserInterface
     */
    protected $parser;
    /**
     * @var CacheInterface|null
     */
    protected $cache;

    public function __construct(ParserInterface $parser, ?CacheInterface $cache = null)
    {
        $this->parser = $parser;
        $this->cache = $cache;
    }

    /**
     * Sets cache layer.
     *
     * @return void
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function supports(mixed $resource)
    {
        return is_string($resource)
            && ($path = $this->findAbsolutePath($resource)) !== false
            && is_file($path)
            && pathinfo($path, PATHINFO_EXTENSION) === 'feature';
    }

    protected function doLoad(mixed $resource): array
    {
        $path = $this->getAbsolutePath($resource);
        if ($this->cache) {
            if ($this->cache->isFresh($path, Filesystem::getLastModified($path))) {
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
    protected function parseFeature(string $path)
    {
        return $this->parser->parseFile($path);
    }
}
