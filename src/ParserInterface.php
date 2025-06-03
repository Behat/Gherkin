<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin;

use Behat\Gherkin\Exception\ParserException;
use Behat\Gherkin\Node\FeatureNode;

interface ParserInterface
{
    /**
     * Parses a Gherkin document string and returns feature (or null when none found).
     *
     * @param string $input Gherkin string document
     * @param string|null $file File name
     *
     * @return FeatureNode|null
     *
     * @throws ParserException
     */
    public function parse(string $input, ?string $file = null);

    /**
     * Parses a Gherkin file and returns feature (or null when none found).
     *
     * @throws ParserException
     */
    public function parseFile(string $file): ?FeatureNode;
}
