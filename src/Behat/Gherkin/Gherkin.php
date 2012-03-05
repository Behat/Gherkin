<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Loader\LoaderInterface,
    Behat\Gherkin\Filter\FilterInterface,
    Behat\Gherkin\Filter\LineFilter;

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
        $beginLineFilter = null;
        $endLineFilter = null;

        $matches = array();
        if (preg_match('/^(.*)\:(\d+)\:(\d+)$/', $resource, $matches)) {
            $resource = $matches[1];
            $beginLineFilter = new LineFilter($matches[2]);
            $endLineFilter = new LineFilter($matches[3]);
        } else if (preg_match('/^(.*?)\:(\d+)$/', $resource, $matches)) {
            $resource = $matches[1];
            $beginLineFilter = new LineFilter($matches[2]);
            $endLineFilter = $beginLineFilter;
        }

        $loader = $this->resolveLoader($resource);

        if (null === $loader) {
            throw new \InvalidArgumentException(sprintf('Can\'t find loader for resource: %s', $resource));
        }

        $features = $loader->load($resource);

        foreach ($features as $feature) {
            $scenarios = $feature->getScenarios();
            foreach ($scenarios as $i => $scenario) {
                if (!is_null($beginLineFilter) && $beginLineFilter->isScenarioPreceding($scenario)
                        || !is_null($endLineFilter) && $endLineFilter->isScenarioFollowing($scenario)) {
                    unset($scenarios[$i]);
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
