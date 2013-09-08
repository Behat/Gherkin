<?php

namespace Behat\Gherkin;

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Behat\Gherkin\Filter\FilterInterface;
use Behat\Gherkin\Filter\LineFilter;
use Behat\Gherkin\Filter\LineRangeFilter;
use Behat\Gherkin\Loader\FileLoaderInterface;
use Behat\Gherkin\Loader\LoaderInterface;
use InvalidArgumentException;

/**
 * Gherkin manager.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Gherkin
{
    /**
     * @var LoaderInterface[]
     */
    protected $loaders = array();
    /**
     * @var FilterInterface[]
     */
    protected $filters = array();

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
     * @param FilterInterface $filter Feature/Scenario filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
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
     * @param mixed             $resource Resource to load
     * @param FilterInterface[] $filters  Additional filters
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function load($resource, array $filters = array())
    {
        $filters = array_merge($this->filters, $filters);

        $matches = array();
        if (preg_match('/^(.*)\:(\d+)-(\d+|\*)$/', $resource, $matches)) {
            $resource = $matches[1];
            $filters[] = new LineRangeFilter($matches[2], $matches[3]);
        } elseif (preg_match('/^(.*)\:(\d+)$/', $resource, $matches)) {
            $resource = $matches[1];
            $filters[] = new LineFilter($matches[2]);
        }

        $loader = $this->resolveLoader($resource);

        if (null === $loader) {
            if ($resource) {
                $message = sprintf('Can\'t find applicable feature loader for: "%s"', $resource);
            } else {
                $message = sprintf('Can\'t find applicable feature loader');
            }

            throw new InvalidArgumentException(
                $message . "\n" .
                'Maybe you\'ve forgot to create `features/` folder?'
            );
        }

        $features = array();
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
     * @return LoaderInterface
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
