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
 * File Loader interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
interface FileLoaderInterface extends LoaderInterface
{
    /**
     * Sets base features path.
     *
     * @param string $path Base loader path
     */
    public function setBasePath($path);
}
