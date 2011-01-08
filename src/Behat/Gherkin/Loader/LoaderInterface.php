<?php

namespace Behat\Gherkin\Loader;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Gherkin Loader interface.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface LoaderInterface
{
    /**
     * Check if current loader supports provided resource.
     *
     * @param   string  $resource
     * 
     * @return  boolean
     */
    function supports($resource);

    /**
     * Load features from provided resource.
     *
     * @param   string  $resource
     * 
     * @return  array
     */
    function load($resource);
}
