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
     * Parses input & returns features array.
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
     * Parses a Gherkin file and returns features array.
     *
     * @throws ParserException
     */
    public function parseFile(string $file): ?FeatureNode;
}
