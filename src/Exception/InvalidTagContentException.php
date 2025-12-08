<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Exception;

class InvalidTagContentException extends ParserException
{
    public function __construct(string $tag, ?string $file)
    {
        parent::__construct(
            sprintf(
                'Tags cannot include whitespace, found "%s"%s',
                $tag,
                is_string($file)
                    ? "in file {$file}"
                    : ''
            ),
        );
    }
}
