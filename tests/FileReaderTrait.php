<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use RuntimeException;

trait FileReaderTrait
{
    private static function readFile(string $filePath): string
    {
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new RuntimeException("Could not read file: $filePath");
        }

        return $data;
    }
}
