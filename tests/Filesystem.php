<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

class Filesystem
{
    public static function readFile(string $fileName): string
    {
        $data = @file_get_contents($fileName);
        if ($data === false) {
            throw new \RuntimeException("Failed to read file: $fileName");
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public static function find(string $pattern): array
    {
        return glob($pattern) ?: [];
    }
}
