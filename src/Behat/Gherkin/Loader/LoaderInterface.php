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
 * Loader interface.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface LoaderInterface
{
    /**
     * Checks if current loader supports provided resource.
     *
     * @param   string  $resource
     *
     * @return  boolean
     */
    function supports($resource);

    /**
     * Loads features from provided resource.
     *
     * @param   string  $resource
     *
     * @return  array
     */
    function load($resource);
}
