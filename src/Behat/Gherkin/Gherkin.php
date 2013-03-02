<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Loader\LoaderInterface,
    Behat\Gherkin\Filter\FilterInterface,
    Behat\Gherkin\Filter\LineFilter,
    Behat\Gherkin\Filter\LineRangeFilter;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin manager.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Gherkin
{
    protected $freeze  = true;
    protected $loaders = array();
    protected $filters = array();

    /**
     * Either to freeze features after loading or not.
     *
     * @param Boolean $freeze To freeze?
     */
    public function setFreeze($freeze = true)
    {
        $this->freeze = (bool) $freeze;
    }

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
            $loader->setBasePath($path);
        }
    }

    /**
     * Loads & filters resource with added loaders.
     *
     * @param mixed $resource Resource to load
     * @param array $filters  Additional filters
     *
     * @return array
     *
     * @throws \InvalidArgumentException
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

            throw new \InvalidArgumentException(
                $message."\n".
                'Maybe you\'ve forgot to create `features/` folder?'
            );
        }

        $features = $loader->load($resource);
        foreach ($features as $i => $feature) {
            foreach ($filters as $filter) {
                $filter->filterFeature($feature);

                if (!$feature->hasScenarios() && !$filter->isFeatureMatch($feature)) {
                    unset($features[$i]);
                    continue;
                }
            }

            if ($this->freeze) {
                $feature->freeze();
            }
        }

        return array_values($features);
    }

    /**
     * Resolves loader by resource.
     *
     * @param mixed $resoruce Resource to load
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
