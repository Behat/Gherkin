<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use Behat\Gherkin\Filter\FeatureFilterInterface;
use Behat\Gherkin\Filter\LineFilter;
use Behat\Gherkin\Filter\LineRangeFilter;
use Behat\Gherkin\Loader\FileLoaderInterface;
use Behat\Gherkin\Loader\LoaderInterface;

/**
 * Gherkin manager.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Gherkin
{
    /**
     * @deprecated this constant will not be updated for releases after 4.8.0 and will be removed in the next major.
     * You can use composer's runtime API to get the behat version if you need it. Note that composer's versions will
     * not always be simple numeric values.
     */
    public const VERSION = '4.8.0';

    /**
     * @var list<LoaderInterface>
     */
    protected $loaders = [];
    /**
     * @var list<FeatureFilterInterface>
     */
    protected $filters = [];

    /**
     * Adds loader to manager.
     *
     * @param LoaderInterface $loader Feature loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Adds filter to manager.
     *
     * @param FeatureFilterInterface $filter Feature filter
     */
    public function addFilter(FeatureFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Sets filters to the parser.
     *
     * @param array<array-key, FeatureFilterInterface> $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = [];
        array_map($this->addFilter(...), $filters);
    }

    /**
     * Sets base features path.
     *
     * @param string $path Loaders base path
     */
    public function setBasePath($path)
    {
        foreach ($this->loaders as $loader) {
            if ($loader instanceof FileLoaderInterface) {
                $loader->setBasePath($path);
            }
        }
    }

    /**
     * Loads & filters resource with added loaders.
     *
     * @param mixed $resource Resource to load
     * @param array<array-key, FeatureFilterInterface> $filters Additional filters
     *
     * @return array
     */
    public function load($resource, array $filters = [])
    {
        $filters = array_merge($this->filters, $filters);

        $matches = [];
        if (preg_match('/^(.*):(\d+)-(\d+|\*)$/', $resource, $matches)) {
            $resource = $matches[1];
            $filters[] = new LineRangeFilter($matches[2], $matches[3]);
        } elseif (preg_match('/^(.*):(\d+)$/', $resource, $matches)) {
            $resource = $matches[1];
            $filters[] = new LineFilter($matches[2]);
        }

        $loader = $this->resolveLoader($resource);

        if ($loader === null) {
            return [];
        }

        $features = [];
        foreach ($loader->load($resource) as $feature) {
            foreach ($filters as $filter) {
                $feature = $filter->filterFeature($feature);

                if (!$feature->hasScenarios() && !$filter->isFeatureMatch($feature)) {
                    continue 2;
                }
            }

            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Resolves loader by resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return LoaderInterface|null
     */
    public function resolveLoader($resource)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($resource)) {
                return $loader;
            }
        }

        return null;
    }
}
