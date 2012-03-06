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
 * @author     Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Gherkin
{
    protected $loaders = array();
    protected $filters = array();

    /**
     * Adds loader to manager.
     *
     * @param   Behat\Gherkin\Loader\LoaderInterface    $loader loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Adds filter to manager.
     *
     * @param   Behat\Gherkin\Filter\FilterInterface    $filter filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Sets base features path.
     *
     * @param   string  $path
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
     * @param   mixed   $resource   resource to load
     *
     * @return  array               features
     */
    public function load($resource)
    {
        $filters = $this->filters;

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
            throw new \InvalidArgumentException(sprintf('Can\'t find loader for resource: %s', $resource));
        }

        $features = $loader->load($resource);

        foreach ($features as $feature) {
            $scenarios = $feature->getScenarios();
            foreach ($scenarios as $i => $scenario) {
                foreach ($filters as $filter) {
                    if (!$filter->isScenarioMatch($scenario)) {
                        unset($scenarios[$i]);
                        break;
                    }
                }
            }

            $feature->setScenarios($scenarios);
        }

        return $features;
    }

    /**
     * Resolves loader by resource.
     *
     * @param   mixed   $resoruce   resource to load
     *
     * @return  Behat\Gherkin\Loader\LoaderInterface    loader for resource
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
