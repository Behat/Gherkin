<?php

namespace Behat\Gherkin\Keywords;

/*
 * This file is part of the Behat Gherkin.
 * (c) 2011 Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * File initializable keywords holder.
 *
 * $keywords = new Behat\Gherkin\Keywords\CachedArrayKeywords($file);
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CachedArrayKeywords extends ArrayKeywords
{
    /**
     * Initializes holder with file.
     *
     * @param string $file Cached array path
     */
    public function __construct($file)
    {
        parent::__construct(include($file));
    }
}
