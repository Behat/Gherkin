<?php

namespace Behat\Gherkin;

use Behat\Gherkin\Loader\LoaderInterface,
    Behat\Gherkin\Filter\FilterInterface;

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
     * Add loader to manager. 
     * 
     * @param   LoaderInterface $loader loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Add filter to manager. 
     * 
     * @param   FilterInterface $filter filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Load & filter resource by added loaders. 
     * 
     * @param   mixed   $resource   resource to load
     *
     * @return  array               features
     */
    public function load($resource)
    {
        $loader = $this->resolveLoader($resource);

        if (null === $loader) {
            throw new \InvalidArgumentException(sprintf('Can\'t find loader for resource: %s', $resource));
        }

        $features = $loader->load($resource);

        foreach ($features as $feature) {
            $scenarios = $feature->getScenarios();
            foreach ($scenarios as $i => $scenario) {
                foreach ($this->filters as $filter) {
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
     * Resolve loader by resource. 
     * 
     * @param   mixed   $resoruce   resource to load
     *
     * @return  LoaderInterface     loader for resource
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
