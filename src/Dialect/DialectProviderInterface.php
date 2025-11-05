<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Dialect;

use Behat\Gherkin\Exception\NoSuchLanguageException;

interface DialectProviderInterface
{
    /**
     * @param non-empty-string $language
     *
     * @throws NoSuchLanguageException when the language is not supported
     */
    public function getDialect(string $language): GherkinDialect;

    public function getDefaultDialect(): GherkinDialect;
}
