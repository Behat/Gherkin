<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

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
     *
     * @return void
     */
    public function setBasePath($path);
}
