<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Keywords;

/**
 * File initializable keywords holder.
 *
 * $keywords = new Behat\Gherkin\Keywords\CachedArrayKeywords($file);
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CachedArrayKeywords extends ArrayKeywords
{
    private const I18N_FILE_LOCATION = __DIR__ . '/../../../../i18n.php';

    /**
     * Initializes holder with file.
     *
     * @param string $file Cached array path
     */
    public function __construct($file = null)
    {
        parent::__construct(require $file ?? self::I18N_FILE_LOCATION);
    }
}
